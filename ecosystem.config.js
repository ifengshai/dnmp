module.exports = {
    apps: [
        {
            "name": "mojing_order_sync",
            "script": "sudo docker exec php7.3 php /var/www/mojing/public/admin_1biSSnWyfW.php shell/order_data/process_order_data",
            "exec_mode": "fork",
            "max_memory_restart": "100M",
        },
        {
            "name": "mojing_web_data_sync",
            "script": "sudo docker exec php7.3 php /var/www/mojing/public/admin_1biSSnWyfW.php shell/kafka/web_data/syc_data",
            "exec_mode": "fork",
            "max_memory_restart": "100M",
        },
        {
            "name": "mojing_queue",
            "script": "sudo docker exec --workdir=/var/www/mojing/ php7.3  php think queue:work --queue logisticsJobQueue --daemon --tries 3",
            "exec_mode": "fork",
            "max_memory_restart": "100M",
        },
        {
            "name": "mojing_zendesk_sync_queue",
            "script": "think",
            "interpreter": "php",
            "args": [
                "queue:work",
                "--queue",
                "zendeskJobQueue",
                "--daemon"
            ],
            "exec_mode": "fork",
            "max_memory_restart": "100M",
        }
    ]
};
