<?php

/*
 * 应用从 PHP 端发出的所有通知：操作提示（成功、错误、警告、信息）和错误页文字。
 * 文案请在此处修改，不要改控制器。:count 等占位符由代码填充。
 */
return [
    'admin' => [
        'design_created' => '设计已创建。',
        'design_saved' => '设计已保存。',
        'design_deleted' => '设计已删除。',
        'design_toggled' => ':field 已更新。',
        'fields' => ['published' => '发布状态', 'featured' => '精选状态'],
        'design_has_paid_orders' => '此设计已有 :count 个已付款订单。请改为取消发布，或在地址中加 force 强制删除。',
        'trending_recomputed' => '热门分数已重新计算。',
        'panoramas_not_two_to_one' => '有 :count 张标记为 360° 的图片不是全景相机输出的 2:1 格式，已从 360° 导览中排除并按普通照片显示：:names。请上传等距柱状投影图片以使用导览。',

        'style_created' => '风格已创建。',
        'style_saved' => '风格已保存。',
        'style_deleted' => '风格已删除。',
        'style_has_designs' => '此风格下仍有设计，请先移动它们。',
        'room_created' => '空间类型已创建。',
        'room_saved' => '空间类型已保存。',
        'room_deleted' => '空间类型已删除。',
        'room_has_designs' => '此空间类型下仍有设计，请先移动它们。',

        'order_marked' => '订单已标记为 :status。',
        'customer_updated' => '客户已更新。',
        'customer_deleted' => '客户已删除。',
        'own_account_change' => '不能修改自己的角色或状态。',
        'own_account_delete' => '不能删除自己。',

        'bulk_done' => ':count 个:items已:action。',
        'bulk_orders' => ':count 个订单已标记为 :status。',
        'bulk_designs_kept' => '其中 :count 个有已付款订单，已保留；请改为取消发布。',
        'bulk_taxonomy_kept' => '其中 :count 个仍有设计，已保留。',
        'bulk_self_skipped' => '已跳过您自己的账户。',
        'nouns' => ['designs' => '设计', 'styles' => '风格', 'rooms' => '空间类型', 'accounts' => '账户'],
        'actions' => [
            'publish' => '发布', 'unpublish' => '取消发布', 'feature' => '设为精选', 'unfeature' => '取消精选',
            'delete' => '删除', 'activate' => '启用', 'deactivate' => '停用',
            'make_customer' => '设为客户', 'make_admin' => '设为管理员',
        ],

        'theme_activated' => ':theme 现已成为所有访客看到的主题。',
        'settings_saved' => '设置已保存，网站即时生效。',
        'admin_only' => '仅限管理员访问。',
    ],

    'crop' => [
        'no_gd' => '浏览器无法裁剪此图片，且服务器未安装 GD 图像扩展。请先将图片上传到本站，再进行裁剪。',
        'unreadable' => '无法读取该图片。只能裁剪本站图片或公开可访问的图片地址。',
        'not_an_image' => '该文件不是可裁剪的图片。',
        'failed' => '图片裁剪失败。',
    ],

    'download' => [
        'locked' => '解锁此设计后即可下载图片。',
        'no_zip_extension' => '下载需要 PHP 的 "zip" 扩展，但服务器未安装。',
        'no_temp_file' => '无法创建压缩包的临时文件。',
        'zip_failed' => '无法创建压缩包。',
        'no_files' => '在服务器上找不到此设计的任何图片文件。请确认文件位于 public/ 或 storage/app/public 下，并已执行 "php artisan storage:link"。',
    ],

    'checkout' => ['demo_disabled' => '已配置 Stripe 时，演示支付不可用。'],
    'auth' => ['deactivated' => '该账户已被停用。'],
    'newsletter' => ['subscribed' => '谢谢，您已加入订阅列表。'],
];
