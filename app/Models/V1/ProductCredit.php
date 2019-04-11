<?php
namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProductCredit extends Model
{
    /**
     * The database connection to use
     * @var string
     */
    protected $connection = 'reseller';

    /**
     * The table associated with the model.
     * Uses the database_name.table_name syntax, avoiding use of default database driver config (with ddosx database)
     *
     * @var string
     */
    protected $table = 'product_credit';

    /**
     * The table's primary key
     * @var string
     */
    protected $primaryKey = 'product_credit_id';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'product_credit_id'          => 'integer',
        'product_credit_reseller_id' => 'integer',
        'product_credit_reference'   => 'string',
        'product_credit_amount'      => 'integer'
    ];

    /**
     * The values we can populate with mass assignment
     *
     * @var array
     */
    protected $fillable = [
        'product_credit_reseller_id',
        'product_credit_reference',
        'product_credit_amount'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Scope a query to only include product credits which belong
     * to the current reseller unless you're admin
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param $resellerId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithReseller($query, $resellerId)
    {
        $resellerId = filter_var($resellerId, FILTER_SANITIZE_NUMBER_INT);

        if (!empty($resellerId)) {
            return $query->where('product_credit_reseller_id', $resellerId);
        }
        return $query;
    }

    /**
     * Get the credits a reseller can use
     * @param Builder $query
     * @param int     $resellerId
     * @param string  $reference
     * @return int
     */
    public function scopeGetRemainingResellerCredits(Builder $query, int $resellerId, string $reference): int
    {
        $result = $query->leftJoin(
            DB::raw('(
                SELECT
                    product_credit_product_credit_id,
                    SUM(IFNULL(product_credit_product_credit_cost, 0)) AS total_credits_used
                FROM
                    product_credit_product
                WHERE
                    product_credit_product_active = "Yes"
                GROUP BY product_credit_product_credit_id
            ) credit_products'),
            function ($join) {
                $join->on(
                    'product_credit.product_credit_id',
                    '=',
                    'credit_products.product_credit_product_credit_id'
                );
            }
        )
            ->where('product_credit_reseller_id', '=', $resellerId)
            ->where('product_credit_reference', '=', $reference)
            ->selectRaw(
                'SUM(product_credit.product_credit_amount) - SUM(IFNULL(credit_products.total_credits_used,'.
                ' 0)) as total_credits_left'
            )
            ->first();
        if (empty($result)) {
            return 0;
        }

        return (int)$result->total_credits_left;
    }

    public function scopeAllResellerCreditsByReference(Builder $query, int $resellerId, string $reference)
    {
        return $query->
        where("product_credit_reference", '=', $reference)->
        where("product_credit_reseller_id", '=', $resellerId);
    }

    /**
     * Method which returns a relation between the ProductCredit and ProductCreditProduct models.
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function productCreditProducts()
    {
        return $this->hasMany(
            'App\Models\V1\ProductCreditProduct',
            "product_credit_product_credit_id"
        );
    }
}
