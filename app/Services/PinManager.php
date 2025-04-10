<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PinManager
{
    /**
     * Generate a new unique 6-digit PIN
     *
     * @return string
     */
    public function generateUniquePin(): string
    {
        $attempts = 0;
        $maxAttempts = 10;
        
        do {
            // Generate a random 6-digit PIN
            $pin = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Check if it already exists
            $exists = User::where('pin', $pin)->exists();
            
            $attempts++;
            
            // If we found a unique PIN or reached max attempts, exit the loop
            if (!$exists || $attempts >= $maxAttempts) {
                break;
            }
        } while (true);
        
        // If we reached max attempts and still couldn't find a unique PIN,
        // try a different strategy
        if ($exists) {
            $pin = $this->generateSequentialPin();
        }
        
        return $pin;
    }
    
    /**
     * Generate a sequential PIN by finding the highest PIN and incrementing it
     *
     * @return string
     */
    protected function generateSequentialPin(): string
    {
        // Find the highest numerical PIN in the system
        $highestPin = User::max('pin');
        
        // If no PINs exist yet, start from 000001
        if (!$highestPin) {
            return '000001';
        }
        
        // Increment the highest PIN by 1
        $nextPin = (int)$highestPin + 1;
        
        // If we've used all possible 6-digit PINs (unlikely), start over from 1
        if ($nextPin > 999999) {
            $nextPin = 1;
        }
        
        // Format as 6-digit string with leading zeros
        return str_pad($nextPin, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate a batch of unique PINs
     *
     * @param int $count Number of PINs to generate
     * @return array
     */
    public function generateBatchPins(int $count): array
    {
        $pins = [];
        
        for ($i = 0; $i < $count; $i++) {
            $pins[] = $this->generateUniquePin();
        }
        
        return $pins;
    }
    
    /**
     * Validate if a PIN meets the system requirements
     *
     * @param string $pin
     * @return bool
     */
    public function isValidPin(string $pin): bool
    {
        // Check if PIN is exactly 6 digits
        if (!preg_match('/^[0-9]{6}$/', $pin)) {
            return false;
        }
        
        // Reject sequential PINs (like 123456)
        if (preg_match('/(012345|123456|234567|345678|456789|567890)/', $pin)) {
            return false;
        }
        
        // Reject repeating digits (like 111111, 222222)
        if (preg_match('/^(\d)\1{5}$/', $pin)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Reset a user's PIN to a new unique value
     *
     * @param User $user
     * @return string The new PIN
     */
    public function resetUserPin(User $user): string
    {
        $newPin = $this->generateUniquePin();
        
        $user->update(['pin' => $newPin]);
        
        return $newPin;
    }
}
