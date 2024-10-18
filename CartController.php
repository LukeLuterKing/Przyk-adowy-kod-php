<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRequest;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function addToCart(CartRequest $request, $productId): \Illuminate\Http\RedirectResponse
    {
        $request->validated();

        if (Auth::check()) {
            $userId = Auth::id();

            $cartItem = Cart::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();

            if ($cartItem) {
                $cartItem->quantity += $request->input('quantity', 1);
                $cartItem->save();
            } else {
                Cart::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'quantity' => $request->input('quantity', 1)
                ]);
            }
        } else {
            if (!$request->session()->has('session_id')) {
                $request->session()->put('session_id', Str::uuid()->toString());
            }

            $sessionId = $request->session()->get('session_id');

            $cartItem = Cart::where('session_id', $sessionId)
                ->where('product_id', $productId)
                ->first();

            if ($cartItem) {
                $cartItem->quantity += $request->input('quantity', 1);
                $cartItem->save();
            } else {
                Cart::create([
                    'session_id' => $sessionId,
                    'product_id' => $productId,
                    'quantity' => $request->input('quantity', 1),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Produkt dodany do koszyka!');
    }

    public function index(Request $request)
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $cartItems = Cart::where('user_id', $userId)->with('product')->get();
        } else {
            $sessionId = $request->session()->get('session_id');
            $cartItems = Cart::where('session_id', $sessionId)->with('product')->get();
        }

        foreach ($cartItems as $item) {
            if (!$item->product) {
                $item->delete();
                return redirect('/')->with('error', 'Niektóre produkty w Twoim koszyku zostały usunięte.');
            }
        }

        $totalAmount = $cartItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        return view('shop.cart', compact('cartItems', 'totalAmount'));
    }

    public function remove(Request $request, $productId)
    {
        if (Auth::check()) {
            $userId = Auth::id();
            Cart::where('user_id', $userId)->where('product_id', $productId)->delete();
        } else {
            $sessionId = $request->session()->get('session_id');
            Cart::where('session_id', $sessionId)->where('product_id', $productId)->delete();
        }

        return redirect()->route('cart.index')->with('success', 'Produkt został usunięty z koszyka.');
    }

    public function mergeCartAfterLogin(Request $request)
    {
        $sessionId = $request->session()->get('session_id');
        $userId = Auth::id();

        if ($sessionId) {
            $guestCartItems = Cart::where('session_id', $sessionId)->get();
            $userCartItems = Cart::where('user_id', $userId)->get();

            foreach ($guestCartItems as $guestItem) {
                $userCartItem = $userCartItems->where('product_id', $guestItem->product_id)->first();

                if ($userCartItem) {
                    $userCartItem->quantity += $guestItem->quantity;
                    $userCartItem->save();
                } else {
                    $guestItem->user_id = $userId;
                    $guestItem->session_id = null;
                    $guestItem->save();
                }
            }

            Cart::where('session_id', $sessionId)->delete();
        }

        return redirect()->route('cart.index');
    }

    public function updateQuantity($productId, CartRequest $request)
    {
        $request->validated();

        $cartItem = Cart::where('product_id', $productId)->first();

        if (!$cartItem) {
            return redirect()->back()->with('error', 'Produktu nie ma w koszyku');
        }

        $cartItem->quantity = (int) $request->input('quantity');
        $cartItem->save();

        return redirect()->back()->with('success', 'Zaktualizowano koszyk');
    }
}
