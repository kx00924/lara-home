<?php

/*
 * Every notification the application sends from PHP: flash messages (success,
 * error, warning, info) and error-page texts. Edit wording here, not in the
 * controllers. Placeholders such as :count are filled in by the code.
 */
return [
    'admin' => [
        'design_created' => 'Design created.',
        'design_saved' => 'Design saved.',
        'design_deleted' => 'Design deleted.',
        'design_toggled' => ':field updated.',
        'fields' => ['published' => 'Published', 'featured' => 'Featured'],
        'design_has_paid_orders' => 'This design has :count paid order(s). Unpublish it instead, or delete with force.',
        'trending_recomputed' => 'Trending scores recomputed.',
        'panoramas_not_two_to_one' => ':count image(s) marked 360° are not in the 2:1 format 360° cameras produce, so they are left out of the 360° tour and shown as normal photos: :names. Upload equirectangular images to use the tour.',

        'style_created' => 'Style created.',
        'style_saved' => 'Style saved.',
        'style_deleted' => 'Style deleted.',
        'style_has_designs' => 'This style still has designs. Move them first.',
        'room_created' => 'Room type created.',
        'room_saved' => 'Room type saved.',
        'room_deleted' => 'Room type deleted.',
        'room_has_designs' => 'This room type still has designs. Move them first.',

        'order_marked' => 'Order marked :status.',
        'customer_updated' => 'Customer updated.',
        'customer_deleted' => 'Customer deleted.',
        'own_account_change' => 'You cannot change your own role or status.',
        'own_account_delete' => 'You cannot delete yourself.',

        // Bulk actions: ":count :items :action." e.g. "3 design(s) published."
        'bulk_done' => ':count :items :action.',
        'bulk_orders' => ':count order(s) marked :status.',
        'bulk_designs_kept' => ' :count with paid orders were kept; unpublish those instead.',
        'bulk_taxonomy_kept' => ' :count still have designs and were kept.',
        'bulk_self_skipped' => ' Your own account was skipped.',
        'nouns' => ['designs' => 'design(s)', 'styles' => 'style(s)', 'rooms' => 'room type(s)', 'accounts' => 'account(s)'],
        'actions' => [
            'publish' => 'published', 'unpublish' => 'unpublished', 'feature' => 'featured', 'unfeature' => 'unfeatured',
            'delete' => 'deleted', 'activate' => 'activated', 'deactivate' => 'deactivated',
            'make_customer' => 'set to customer', 'make_admin' => 'set to admin',
        ],

        'theme_activated' => ':theme is now the live theme for all visitors.',
        'settings_saved' => 'Settings saved. The site updates immediately.',
        'admin_only' => 'Admin access only.',
    ],

    'crop' => [
        'no_gd' => 'This image could not be cropped in the browser, and the server has no GD image extension. Upload the image to this site first, then crop it.',
        'unreadable' => 'The image could not be read. Only images on this site or public URLs can be cropped.',
        'not_an_image' => 'This file is not an image that can be cropped.',
        'failed' => 'The image could not be cropped.',
    ],

    'download' => [
        'locked' => 'Unlock this design to download its images.',
        'no_zip_extension' => 'Downloads need the PHP "zip" extension, which is not installed on this server.',
        'no_temp_file' => 'Could not create a temporary file for the zip.',
        'zip_failed' => 'Could not create the zip file.',
        'no_files' => 'None of this design\'s image files were found on the server. Check that the files exist under public/ or storage/app/public and that "php artisan storage:link" has been run.',
    ],

    'checkout' => ['demo_disabled' => 'Demo payments are disabled when Stripe is configured.'],
    'auth' => ['deactivated' => 'This account has been deactivated.'],
    'newsletter' => ['subscribed' => 'Thank you. You are on the list.'],
];
