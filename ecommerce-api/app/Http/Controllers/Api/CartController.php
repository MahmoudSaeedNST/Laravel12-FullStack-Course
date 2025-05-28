<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
       // get authenticated user
       $user = $request->user();
       // get user's cart items
       $cartItems = Cart::where('user_id', $user->id)->with('product')->get();
       // get total of cart items
        $total = $cartItems->sum(function ($item) {
              return $item->product->price * $item->quantity;
         });
         // return response
         return response()->json([
            'suscess' => true,
            'message' => 'Cart items retrieved successfully',
            'cart' => $cartItems,
            'total' => round($total, 2),
         ]);
    }


    /* Add a new item to the cart
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       // get authenticated user
       $user = $request->user();
       // validate request
       $data = $request->validate([
          'product_id' => 'required|exists:products,id',
          'quantity' => 'required|integer|min:1',
       ]);

       

       // get cart item
       $cartItem = Cart::where('user_id', $user->id)->where('product_id', $data['product_id'])->first();
       // check if cart item exists
         if ($cartItem) { // if not null, just update the quantity
             // update cart item
             $cartItem->quantity += $data['quantity'];
             $cartItem->save();
             // return response
             return response()->json([
                 'success' => true,
                 'message' => 'Cart item updated successfully',
                 'cart_item' => $cartItem,
             ], 200);
         } else {
             // create new cart item
             $cartItem = Cart::create([
                 'user_id' => $user->id,
                 'product_id' => $data['product_id'],
                 'quantity' => $data['quantity'],
             ]);

             return response()->json([
                 'success' => true,
                 'message' => 'Cart item added successfully',
                 'cart_item' => $cartItem,
             ], 201);
         }

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cart $cart)
    {
        // validate request
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // update cart item
        $cart->quantity = $data['quantity'];
        $cart->save();
        // return response
        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully',
            'cart_item' => $cart,
        ], 200);
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cart $cart)
    {
        // remove 1 cart item
        // delete cart item
        $cart->delete();
        // return response
        return response()->json([
            'success' => true,
            'message' => 'Cart item deleted successfully',
        ], 200);
    }
    
    // clear cart

}
