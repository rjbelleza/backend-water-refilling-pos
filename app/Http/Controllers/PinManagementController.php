<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PinManager;
use Illuminate\Http\Request;

class PinManagementController extends Controller
{
    protected $pinManager;
    
    public function __construct(PinManager $pinManager)
    {
        $this->pinManager = $pinManager;
        $this->middleware(['auth:sanctum', 'ability:admin']);
    }
    
    public function index()
    {
        $users = User::select('id', 'name', 'pin', 'role', 'created_at')->get();
        
        return response()->json(['users' => $users]);
    }
    
    public function resetPin(Request $request, User $user)
    {
        $newPin = $this->pinManager->resetUserPin($user);
        
        return response()->json([
            'message' => "PIN for {$user->name} has been reset",
            'pin' => $newPin
        ]);
    }
    
    public function generatePin()
    {
        $pin = $this->pinManager->generateUniquePin();
        
        return response()->json(['pin' => $pin]);
    }
    
    public function assignPin(Request $request, User $user)
    {
        $validated = $request->validate([
            'pin' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/',
                'unique:users,pin,' . $user->id
            ],
        ]);
        
        if (!$this->pinManager->isValidPin($validated['pin'])) {
            return response()->json(
                ['message' => 'The PIN does not meet security requirements.'],
                422
            );
        }
        
        $user->update(['pin' => $validated['pin']]);
        
        return response()->json([
            'message' => "PIN for {$user->name} has been updated."
        ]);
    }
}
