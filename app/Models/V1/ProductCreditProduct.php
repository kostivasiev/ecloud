<?php
namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

class ProductCreditProduct extends Model
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
    protected $table = 'product_credit_product';

    /**
     * The table's primary key
     * @var string
     */
    protected $primaryKey = 'product_credit_product_id';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'product_credit_product_id'           => 'integer',
        'product_credit_product_credit_id'    => 'integer',
        'product_credit_product_reference_id' => 'integer',
        'product_credit_product_credit_cost'  => 'integer',
        'product_credit_product_active'       => 'string'
    ];

    /**
     * The default values for this model.
     *
     * @var array
     */
    protected $attributes = [
        'product_credit_product_credit_cost' => 1,
        'product_credit_product_active'      => 'Yes'
    ];

    /**
     * @return array|Model
     */
    protected $fillable = [
        'product_credit_product_credit_id',
        'product_credit_product_reference_id',
        'product_credit_product_credit_cost',
        'product_credit_product_active'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Scopes a query to return a ProductCreditProduct (use of a product credit).
     * Used for finding a refundable record associated to a reference ID.
     * @param       $query
     * @param array $creditIds
     * @param int   $referenceId
     * @param int   $cost
     * @return mixed
     */
    public function scopeGetForCreditIds($query, array $creditIds, int $referenceId, int $cost)
    {
        return $query
            ->whereIn("product_credit_product_credit_id", $creditIds)
            ->where("product_credit_product_reference_id", $referenceId)
            ->where("product_credit_product_credit_cost", $cost)
            ->withActive();
    }

    /**
     * Scope a query to only include product credit products which are "active"
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithActive($query)
    {
        return $query->where('product_credit_product_active', "Yes");
    }

    /**
     * Method which returns a relation between the ProcuctCreditProduct and ProductCredit models.
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function productCredit()
    {
        return $this->belongsTo('App\Models\V1\ProductCredit', 'product_credit_product_credit_id', 'product_credit_id');
    }
}
