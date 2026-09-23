<?php

namespace App\Http\Controllers;

use App\Models\Design;
use App\Models\Order;
use Illuminate\Http\Request;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    private function stripeKey(): ?string
    {
        return config('services.stripe.secret') ?: null;
    }

    /** POST /designs/{design}/checkout */
    public function start(Request $request, Design $design)
    {
        $user = $request->user();
        if ($design->is_free) {
            return redirect()->route('designs.show', $design);
        }
        if ($user->owns($design)) {
            return redirect()->route('designs.show', $design)->with('info', __('ui.checkout.already_owned'));
        }

        $order = Order::firstOrCreate(
            ['user_id' => $user->id, 'design_id' => $design->id, 'status' => 'pending'],
            ['amount' => $design->price, 'currency' => $design->currency, 'provider' => $this->stripeKey() ? 'stripe' : 'demo']
        );

        if ($this->stripeKey()) {
            Stripe::setApiKey($this->stripeKey());
            $session = StripeSession::create([
                'mode' => 'payment',
                'customer_email' => $user->email,
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($design->currency),
                        'unit_amount' => (int) round($design->price * 100),
                        'product_data' => ['name' => $design->title, 'images' => array_filter([$design->cover])],
                    ],
                ]],
                'metadata' => ['order_id' => $order->id],
                'success_url' => route('checkout.success', ['order' => $order->id]).'&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('designs.show', $design).'?cancelled=1',
            ]);
            $order->update(['provider' => 'stripe', 'provider_ref' => $session->id]);

            return redirect()->away($session->url);
        }

        return redirect()->route('checkout.show', $order);
    }

    /** GET /checkout/{order}: demo checkout page. */
    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        if ($order->status === 'paid') {
            return redirect()->route('checkout.success', ['order' => $order->id]);
        }
        $order->load('design.category', 'design.roomType');

        return view('checkout.show', ['order' => $order, 'demo' => ! $this->stripeKey()]);
    }

    /** POST /checkout/{order}/pay: demo payment (only without Stripe). */
    public function demoPay(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_if($this->stripeKey(), 400, 'Demo payments are disabled when Stripe is configured.');
        if ($request->input('outcome') === 'fail') {
            $order->update(['status' => 'failed']);

            return back()->with('error', __('ui.checkout.declined'));
        }
        $order->markPaid('demo_'.now()->timestamp);

        return redirect()->route('checkout.success', ['order' => $order->id]);
    }

    /** GET /checkout/success?order=&session_id= */
    public function success(Request $request)
    {
        $order = Order::with('design')->findOrFail($request->query('order'));
        abort_unless($order->user_id === $request->user()->id, 403);
        if ($request->query('session_id') && $this->stripeKey() && $order->status !== 'paid') {
            Stripe::setApiKey($this->stripeKey());
            $session = StripeSession::retrieve($request->query('session_id'));
            if ($session->payment_status === 'paid') {
                $order->markPaid($session->id);
            }
        }

        return view('checkout.success', ['order' => $order]);
    }
}
