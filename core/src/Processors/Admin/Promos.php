<?php

namespace App\Processors\Admin;

use App\Model\Promo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class Promos extends \App\ObjectProcessor
{

    protected $class = '\App\Model\Promo';
    protected $scope = 'discounts';


    /**
     * @param Builder $c
     *
     * @return Builder
     */
    protected function beforeCount($c)
    {
        if ($query = trim($this->getProperty('query', ''))) {
            $c->where('code', 'LIKE', "%$query%");
            $c->orWhere('title', 'LIKE', "%$query%");
        }

        return $c;
    }

    /**
     * @param Builder $c
     * @return Builder
     */
    protected function afterCount($c)
    {
        $prefix = $this->container->db->getTablePrefix();
        //$c->withCount('orders');
        $c->leftJoin('orders', function(JoinClause $c) {
            $c->on('orders.promo_id', '=', 'promos.id');
            $c->where('orders.status', 2); // Paid orders only
        });
        $c->groupBy('promos.id');

        $c->select('promos.id', 'code', 'promos.discount', 'percent', 'used', 'date_start', 'date_end', 'promos.limit');
        $c->selectRaw("SUM({$prefix}orders.cost) as orders_cost");
        $c->selectRaw("COUNT({$prefix}orders.id) as orders_count");

        return $c;
    }

    /**
     * @param Promo $object
     *
     * @return array
     */
    public function prepareRow($object)
    {
        $array = $object->toArray();
        $array['active'] = $object->check() === true;

        return $array;
    }

    /**
     * @param Promo $record
     *
     * @return bool|\Slim\Http\Response
     */
    public function beforeDelete($record)
    {
        if ($record->used > 0) {
            return 'Нельзя удалять уже использованные промокоды';
        }

        return true;
    }
}