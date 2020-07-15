<?php
/**
 * @Author: CrashpHb彬
 * @Date: 2020/2/26 14:55
 * @Email: 646054215@qq.com
 */
return [
    'platform'=>[
        1=>'zeelool',
        2=>'voogueme',
        3=>'nihao'
    ],
    'zeelool' => [
        'subdomain' => "zeelooloptical",
        'username' => "complaint@zeelool.com",
        'token' => "wAhNtX3oeeYOJ3RI1i2oUuq0f77B2MiV5upmh11B"
    ],
    'voogueme' => [
        'subdomain' => "voogue",
        'username' => "ww591795345@outlook.com",
        'token' => "FksHlSPfruUXrfXv4rAz5IGFZHbW48UKzMg6H5Go"
    ],
    'nihao' => [
        'subdomain' => "nihao",
        'username' => "wangwei@nextmar.com",
        'token' => "1T6P4ewBZsuGLbHcx9WGpEprConFoEd8H5bPZlER"
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
        5 => 'closed'
    ],
    'template_category' => [
        1 => '售前',
        2 => '售中',
        3 => '售后',
        4 => '物流',
        5 => '超时',
        6 => '疫情',
        7 => '电话',
        8 => '通用',
        9 => '退件',
        10 => '退款',
        11 => '寻求合作',
        12 => '差价链接',
        13 => '长时间未回复',
        14 => '其他'
    ],
    'platform_url'=>[
        1   => 'https://www.zeelool.com/index.php/GBExholSDIhOVRMimieBn0LQ/sales_order/view/order_id/',
        2   => 'https://pc.voogueme.com/index.php/xiaomoshou/sales_order/view/order_id/',
        3   => 'https://pc.nihaooptical.com/index.php/xiaomoshou/sales_order/view/order_id/'
    ],
    'check_order_info_url'=>
        'http://'.$_SERVER['HTTP_HOST'].'/admin_1biSSnWyfW.php/saleaftermanage/order_return/search?ref=addtabs'
    ,
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

We sincerely appreciate your understanding and patience in this harsh time. If you have any further questions, please feel free to let us know. We are always here to help. Thank you very much. Have a good day.',
    //发货2周内
    't9' => 'Hi there,

Thank you for shopping with us at Zeelool.

We want to inform you that your order was shipped out on %s, with tracking number %s (%s)

Here is the latest update on the shipping status: %s

You can track it on our website (https://www.zeelool.com/ordertrack) with your account email or tracking number. The tracking information will normally update in about one week after the shipping date. If FedEx is the shipping courier, you may see the shipping status begins to update after the package arrives at the FedEx facility in California. Please keep this email for reference.

We appreciate your kind understanding and patience. Please feel free to contact us with any questions. Thank you. Have a wonderful day.',
    //发货2到3周
    't10' => 'Hi there,

Thank you for bringing this matter to our attention. We confirmed that your package %s tracking #%s arrived in the US on %s. Per the information provided by China Post, your package is being processed in the local customs for inspection and clearance.

The shipping status will start to update again after the customs pass along the package to USPS for further shipping. You will also get an estimated delivery date when the clearance is completed. Please allow a few more days for the package to clear the customs. You can sign up for the text tracking/email updates service with USPS here https://tools.usps.com/go/TrackConfirmAction_input Please enter the tracking number for the text tracking/email updates to pop up.

Your kind understanding and patience are much appreciated. Please do not hesitate to contact us with any questions or concerns. All our customer service agents will do their best to help you. Thank you. Have a nice day.',
    //发货3到4周
    't11' => 'Hi there,

Thank you for your email. We just sent an inquiry to our shipping partner and got a response from them that your package is delayed in the customs clearance process.

As the government is continuously monitoring the coronavirus situation and declaring the state of emergency, it has an impact on the customs inspection and clearance efficiency. Your package is shipped out by economic shipping, which has a low priority in customs clearance. We sincerely appreciate it if you could stay in patience and allow some time for the package to clear the customs. 

We assure you that the package will reach you. If you have any further questions, please feel free to contact us in your free time. Thank you. Be well and safe. ',
    //发货4到6周
    't12' => 'Hi there,

We are regretting to know that the package took longer than expected to deliver. We are deeply sorry for the bad experience you had to go through with us. 

We want to rectify this issue by offering three solutions to choose from.

1.You will get an exclusive coupon code to take 15% off your next purchase (not applicable to the items that under $10)
2.You will get a refund on the shipping fee ($6.95)
3.You will get reward points in your account which the amount is equal to your order total (i.e. if you spent $100, you will get 100 points that is $10 value)

Please advise which one works better for you. We hope the package will arrive soon. Looking forward to hearing from you soon. We appreciate your kind understanding and bearing with us on this issue. Thank you. Have a wonderful day.',
    //发货6到9周
    't13' => 'Hi there,

Thank you for bringing this issue to our attention. This long-time delay rarely happens to our knowledge. It takes place when there is low availability of cargo planes, the package has to sit in the hub for the next available flight. 

To remedy your long wait, we will offer you three options to choose from as our compensation plan. (compensation plan limited to one per order)
1.You will get a partial refund equal to 20% of your order total.
2.You will get reward points in your account which the amount is equal to your order total (i.e. if you spent $100, you will get 100 points that is $10 value)
3.You will get a freebie with your next purchase, the item should be under $30 value. 

We are waiting for your choice. If you choose option 3, please contact us within 24 hours of the purchase date so we can make the addition to your order. 

We appreciate your kind understanding and patience. Please feel free to contact us with any questions. Thank you. Have a wonderful day.',
    //发货大于9周
    't14' => 'Hi there,

Thank you for bringing this issue to our attention. This long-time delay rarely happens to our knowledge. It takes place when there is low availability of cargo planes, the package has to sit in the hub for the next available flight. 

To remedy your long wait, we will offer you three options to choose from as our compensation plan. (compensation plan limited to one per order)

1.You will get a half refund on the order total.
2.You will get reward points in your account which the amount is equal to your order total (i.e. if you spent $100, you will get 100 points that is $10 value).
3.You will get a freebie with your next purchase, the item should be under $30 value. 

We are waiting for your choice. If you choose option 3, please contact us within 24 hours of the purchase date so we can make the addition to your order. 

We appreciate your kind understanding and patience. Please feel free to contact us with any questions. Thank you. Have a wonderful day.',
    //未发货的模板
    't15' => 'Hi there,

Thank you for shopping with us at zeelool.com.

Our order tracker indicates that your glasses are being processed in our facility. All the lenses must be in the queue to our technicians for machining, days of processing time are necessary. We will send you a shipping notification email and an SMS message with a tracking number when your order is dispatched from our warehouse.

We really appreciate your patience and understanding. Please feel free to contact us if there is any other question.

Have a nice day!',
        //投递失败/可能异常
        't16' => 'Hi there,

Thank you for shopping with us at Zeelool.

We want to inform you that your order was shipped out on %s, with tracking number %s by %s.

Here is the latest update on the shipping status: %s

It shows that there is something wrong with your tracking information. Would you please kindly contact %s to ask them why they’re not able to deliver your parcel for you and arrange the delivery date?

We appreciate your kind understanding and patience. Please feel free to contact us with any questions. Thank you. Have a wonderful day.'
    ]
];