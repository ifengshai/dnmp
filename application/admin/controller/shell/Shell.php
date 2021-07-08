<?php
/**
 * Shell.php
 * @author wangpenglei
 * @date   2021/7/1 10:13
 */

namespace app\admin\controller\shell;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\warehouse\StockSku;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Shell extends Command
{
    protected function configure()
    {
        $this->setName('shell')->setDescription('计划任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->setSkuWarehouseLocationStock();

        $output->writeln("ok");
    }

    /**
     * 清理下架SKU库位
     * @author wangpenglei
     * @date   2021/7/1 10:15
     */
    protected function setSkuWarehouseLocation()
    {
        /**
         * 逻辑
         * 1、所有网站全部下架的sku
         * 2、0可用库存
         * 3、每周自动清理
         * 4、审核通过的商品
         */
        $itemPlatFormSku = new ItemPlatformSku();
        $list = $itemPlatFormSku->alias('a')
            ->where(['a.outer_sku_status' => 2, 'b.available_stock' => ['<=', 0], 'b.item_status' => 3])
            ->where('a.sku', 'not in', function ($query) {
                $query->table('fa_item_platform_sku')->field('sku')->group('sku')->having('count(DISTINCT outer_sku_status)>1');
            })
            ->join(['fa_item' => 'b'], 'a.sku=b.sku')
            ->group('a.sku')
            ->column('a.sku');

        $storeSku = new StockSku();
        $storeSku->alias('a')->where(['sku' => ['in', $list]])
            ->where(['b.type' => 1, 'b.area_id' => ['in', [3, 6]]])
            ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
            ->update(['is_del' => 2]);

        file_put_contents('./store_house.log', serialize($list) . PHP_EOL, FILE_APPEND);
        echo "ok";
    }


    /**
     * 清理下架SKU库位
     * @author wangpenglei
     * @date   2021/7/1 10:15
     */
    protected function setSkuWarehouseLocationStock()
    {
        /**
         * 逻辑
         * 1、所有网站全部下架的sku
         * 2、0可用库存
         * 3、每周自动清理
         * 4、审核通过的商品
         */
        $itemPlatFormSku = new ItemPlatformSku();
        $list = $itemPlatFormSku->alias('a')
            ->where(['a.outer_sku_status' => 2, 'b.stock' => ['<=', 0], 'b.item_status' => 3])
            ->where('a.sku', 'not in', function ($query) {
                $query->table('fa_item_platform_sku')->field('sku')->group('sku')->having('count(DISTINCT outer_sku_status)>1');
            })
            ->join(['fa_item' => 'b'], 'a.sku=b.sku')
            ->group('a.sku')
            ->column('a.sku');

        $storeSku = new StockSku();
        $storeSku->alias('a')->where(['sku' => ['in', $list]])
            ->where(['b.type' => 1, 'b.area_id' => ['in', [3, 6]]])
            ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
            ->update(['is_del' => 2, 'deletetime' => time()]);

        file_put_contents('./store_house.log', serialize($list) . PHP_EOL, FILE_APPEND);
        echo "ok";
    }
}