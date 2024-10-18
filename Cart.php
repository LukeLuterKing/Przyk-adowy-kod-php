<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @method static where(string $column, int|string|null $value)
 */
class Cart extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'carts';

    protected $fillable = ['user_id', 'session_id', 'product_id', 'quantity'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
