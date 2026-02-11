// assets/js/main.js

// API Call Wrapper with proper error handling and logging
async function apiCall(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };
    if (data) {
        options.body = JSON.stringify(data);
    }
    try {
        console.log(`[API] ${method} ${url}`, data || '');
        const response = await fetch(url, options);
        const text = await response.text();
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('[API] Invalid JSON response:', text);
            throw new Error('Server error: Invalid response format. Check console.');
        }
        
        if (!response.ok) {
            const errorMsg = result.message || result.error || 'Request failed';
            console.error(`[API] Error (${response.status}):`, errorMsg);
            throw new Error(errorMsg);
        }
        
        console.log('[API] Success:', result);
        return result;
    } catch (error) {
        console.error('[API] Exception:', error.message);
        alert('Error: ' + error.message);
        throw error;
    }
}

// Format currency values
function formatCurrency(amount) {
    const n = Number(amount) || 0;
    return 'Tsh ' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Logout function
function logout() {
    apiCall('api/auth.php?action=logout', 'POST').then(() => {
        window.location.href = 'index.php';
    }).catch(() => {
        // Still redirect even if API fails
        window.location.href = 'index.php';
    });
}

// Database connection test (for debugging)
async function testDatabaseConnection() {
    try {
        const result = await apiCall('api/data.php?type=products');
        console.log('[DB TEST] Database connection successful! Products:', result);
        return true;
    } catch (error) {
        console.error('[DB TEST] Database connection failed:', error);
        return false;
    }
}

// Run DB test on page load (optional, for debugging)
window.addEventListener('load', () => {
    console.log('[INIT] Page loaded - Database connection test...');
    testDatabaseConnection();
});
