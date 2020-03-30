<?php
/**
 * @Author: CrashpHb彬
 * @Date: 2020/2/26 14:55
 * @Email: 646054215@qq.com
 */
return [
    'zeelool' => [
        'subdomain' => "zeelooloptical",
        'username' => "complaint@zeelool.com",
        'token' => "wAhNtX3oeeYOJ3RI1i2oUuq0f77B2MiV5upmh11B"
    ],
    'voogueme' => [
        'subdomain' => "zeelooloptical",
        'username' => "complaint@zeelool.com",
        'token' => "wAhNtX3oeeYOJ3RI1i2oUuq0f77B2MiV5upmh11B"
    ],
    'priority' => [
        0 => '',
        1 => 'low',
        2 => 'normal',
        3 => 'high',
        4 => 'urgent',
    ],
    'pulbic_type' => [
        'public reply',
        'internal note'
    ],
    'status' => [
        1 => 'new',
        2 => 'open',
        3 => 'pending',
        4 => 'solved',
        5 => 'close'
    ],
    'template_category' => [
        1=> '售前',
        2=> '售中',
        3=> '售后',
        4=> '物流',
        5=> '超时',
        6=> '疫情',
        7=> '电话',
        8=> '其他'
    ],
    'templates' => [
        //自动回复判断性邮件
        't1' => 'Hi there,

We have received your request.

Please kindly reply with the words below (without the beginning number) for a fast response.
1.Order Status
2.Change Information
3.Others

Thank you very much for your cooperation. If you have any further questions, please feel free to let us know. We are always at your service. Have a good day.',
        //Processing状态
        't2' => 'Hi there,

Thank you for shopping at Zeelool.com.

Our order tracker indicates that your glasses are being processed in our facility. Because all the lenses must be in the queue to our technicians for machining, days of processing time are necessary. We will send you a shipping notification email and an SMS message with a tracking number when your order is dispatched from our warehouse.

We really appreciate your patience and kind understanding. Please feel free to contact us if there is any other question. Have a nice day!',
        //3、Pending/Creditcard Failed
        't3' => 'Hi there,

Thank you for reaching out to Zeelool.com.

We noticed that your payment didn’t go through. We suggest you try to pay again if you want to get these glasses. Once you placed your order successfully, the system will send you a confirmation email automatically.

If you see a charge on the statement but the order reads as failed, we recommend you call the card issuer for assistance with this matter. 

We hope this response helps. Please do not hesitate to reach out to us if you have any further questions. Have a nice day!',
        //已签收
        't4' => 'Hi there, 

Thank you for bringing this matter to our attention. We want to inform you that we shipped out your order on %s, with tracking number %s by %s.

Here is the latest update on the shipping status: %s

The latest tracking information indicates that your package was delivered on %s. We want to appreciate it if you can give us the feedback after you receive your purchase.

Thank you for your patience and kind understanding. If you have any further questions, please feel free to let us know. We are always here to help. Thank you very much. Have a good day.',
        //模板4更新时间在7天内
        't5' => 'Hi there, 

Thank you for bringing this matter to our attention. 

We want to inform you that we shipped out your order on %s, with tracking number %s by %s.

Here is the latest update on the shipping status: %s

You can track it on our website %s or AfterShip %s with your tracking number. Please kindly allow a few more days for the delivery.

Thank you for your patience and kind understanding. If you have any further questions, please feel free to let us know. We are always here to help. Thank you very much. Have a good day.',
        //模板5超过7天未更新
        't6' => 'Hi there,

Thank you for bringing this matter to our attention. We want to inform you that we shipped out your order on %s, with tracking number %s by %s.

We have contacted our shipping partner China Post and learned that your package is with the customs for inspection and clearance. 

We have received similar inquiries as yours in the same shipping batch. We may have to ask you to wait in patience for two more weeks to get the glasses. If you haven’t received your glasses in two weeks, please contact us at your convenience for help and resolution. 

We sincerely appreciate your understanding and patience. Have a nice day. ',
        //7、取消订单
        't7' => 'Hi there,

Thank you for contacting us about this matter. We have canceled your order %s per your request. 

We will initiate the refund process in 48 hours. The refund to your credit card (or original method of payment like PayPal) will appear in your statement in 3-7 business days, depending on the card issuer\'s policies.

If you have any further questions, please feel free to let us know. We are always here to help. Thank you very much. Have a good day.',
        't8' => 'Hi there,

We are very regretful for not being able to ship out your order on time. We would like to let you know that the logistics services in China are being heavily affected by the novel coronavirus containment program.

In such a case, would you please kindly wait some time for your order? If you would like to wait, we will expedite your order so that it can be shipped out within 7 business days from today. We will pay great attention to your order and notify you as soon as we ship it out. You will receive a shipping notification email and an SMS message to track the package.

As our appreciation for your kind waiting, you may enjoy a 20% off coupon for your next purchase. You can enter COM20 on the shopping cart page, and then 20% off will automatically apply to the order.

We sincerely appreciate your understanding and patience in this harsh time. If you have any further questions, please feel free to let us know. We are always here to help. Thank you very much. Have a good day.
'
    ]
];