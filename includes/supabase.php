<?php
// ============================================
// SUPABASE PHP CLIENT
// ============================================

class SupabaseClient {
    private $url;
    private $anonKey;
    private $serviceKey;
    
    public function __construct() {
        $this->url = SUPABASE_URL;
        $this->anonKey = SUPABASE_ANON_KEY;
        $this->serviceKey = SUPABASE_SERVICE_KEY;
    }
    
    // Generic SELECT query
    public function select($table, $columns = '*', $conditions = []) {
        $url = $this->url . '/rest/v1/' . $table . '?select=' . $columns;
        
        // Add conditions
        foreach ($conditions as $key => $value) {
            $url .= '&' . $key . '=eq.' . urlencode($value);
        }
        
        return $this->makeRequest('GET', $url, null, $this->anonKey);
    }
    
    // INSERT query
    public function insert($table, $data) {
        $url = $this->url . '/rest/v1/' . $table;
        return $this->makeRequest('POST', $url, $data, $this->serviceKey);
    }
    
    // UPDATE query
    public function update($table, $data, $conditions = []) {
        $url = $this->url . '/rest/v1/' . $table . '?';
        
        foreach ($conditions as $key => $value) {
            $url .= $key . '=eq.' . urlencode($value) . '&';
        }
        
        $url = rtrim($url, '&');
        
        return $this->makeRequest('PATCH', $url, $data, $this->serviceKey);
    }
    
    // DELETE query
    public function delete($table, $conditions = []) {
        $url = $this->url . '/rest/v1/' . $table . '?';
        
        foreach ($conditions as $key => $value) {
            $url .= $key . '=eq.' . urlencode($value) . '&';
        }
        
        $url = rtrim($url, '&');
        
        return $this->makeRequest('DELETE', $url, null, $this->serviceKey);
    }
    
    // Execute RPC (stored procedure)
    public function rpc($functionName, $params = []) {
        $url = $this->url . '/rest/v1/rpc/' . $functionName;
        return $this->makeRequest('POST', $url, $params, $this->serviceKey);
    }
    
    // Make HTTP request
    private function makeRequest($method, $url, $data = null, $apiKey = null) {
        $ch = curl_init();
        
        $headers = [
            'apikey: ' . ($apiKey ?? $this->anonKey),
            'Authorization: Bearer ' . ($apiKey ?? $this->anonKey),
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data !== null && in_array($method, ['POST', 'PATCH', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['error' => $error];
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            return ['error' => $result['message'] ?? 'Request failed', 'code' => $httpCode];
        }
        
        return $result;
    }
    
    // Sign up new user
    public function signUp($email, $password) {
        $url = $this->url . '/auth/v1/signup';
        
        $data = [
            'email' => $email,
            'password' => $password
        ];
        
        return $this->makeRequest('POST', $url, $data, $this->anonKey);
    }
    
    // Sign in user
    public function signIn($email, $password) {
        $url = $this->url . '/auth/v1/token?grant_type=password';
        
        $data = [
            'email' => $email,
            'password' => $password
        ];
        
        return $this->makeRequest('POST', $url, $data, $this->anonKey);
    }
}

// Initialize global Supabase client
$supabase = new SupabaseClient();
?>