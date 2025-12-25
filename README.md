# PHP_Laravel12_Custome_Authentication_Using_Vue.JS


# Step 1: Install Laravel 12 and Create PHP_Laravel12_Custome_Authentication_Using_Vue.JS project
# STEP 2: Composer command (IMPORTANT)
```php
composer create-project laravel/laravel PHP_Laravel12_Custome_Authentication_Using_Vue.JS
``` 
# Step 3 :Database Configuration setup
```php
.env file open \
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your database name
DB_USERNAME=root
DB_PASSWORD=
``` 
# STEP 4: Customers Table Migration Create command
```php
php artisan make:migration create_customers_table
``` 
database/migrations/xxxx_create_customers_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
``` 
Run migration:
```php
php artisan migrate
``` 

# STEP 5: Customer Model Create 
```php
php artisan make:model Customer
``` 
app/Models/Customer.php
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];
}
``` 

# STEP 6: Auth Configuration (IMPORTANT)
```php
config/auth.php
Provider change:
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\Customer::class,
    ],
],
``` 
Laravel is open the customer model not open user model

 # STEP 5: CustomerAuth Controller create command
 ```php
php artisan make:controller CustomerAuthController
``` 
app/Http/Controllers/CustomerAuthController.php
```php
<?php

namespace App\Http\Controllers;

use App\Models\Customer;           // Import Customer model for database operations
use Illuminate\Http\Request;       // Import Request class to handle HTTP requests
use Illuminate\Support\Facades\Hash; // Import Hash facade for password hashing
use Illuminate\Support\Facades\Auth; // Import Auth facade for authentication

/**
 * CustomerAuthController handles customer registration, login, and logout functionality
 * This controller manages authentication specifically for the Customer model (separate from default User model)
 */
class CustomerAuthController extends Controller
{
    /**
     * Handle customer registration
     * 
     * @param Request $request Contains form data (name, email, password)
     * @return RedirectResponse Redirects to dashboard on success
     */
    public function register(Request $request)
    {
        // Validate incoming request data
        // Ensures name is required, email is valid & unique in customers table, password is at least 6 chars
        $request->validate([
            'name' => 'required',                    // Name field is mandatory
            'email' => 'required|email|unique:customers', // Email must be valid and not exist in customers table
            'password' => 'required|min:6',          // Password must be at least 6 characters
        ]);

        // Create new customer record in database
        // Hash::make() securely encrypts the password before storing
        Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Password is hashed for security
        ]);

        // Automatically log in the newly registered customer
        // Finds the customer by email and logs them into the session
        Auth::login(Customer::where('email', $request->email)->first());

        // Redirect to dashboard page after successful registration & login
        return redirect('/dashboard');
    }

    /**
     * Handle customer login
     * 
     * @param Request $request Contains email and password from login form
     * @return RedirectResponse Redirects to dashboard on success, back with error on failure
     */
    public function login(Request $request)
    {
        // Attempt to authenticate customer using email and password
        // Auth::attempt() automatically checks hashed password against database
        // Uses Customer model's guard (configured in auth.php config file)
        if (Auth::attempt($request->only('email', 'password'))) {
            // Login successful - redirect to dashboard
            return redirect('/dashboard');
        }

        // Login failed - redirect back to login page with error message
        // 'error' session flash data will be displayed in login view
        return back()->with('error', 'Invalid credentials');
    }

    /**
     * Handle customer logout
     * 
     * @return RedirectResponse Redirects to login page after logout
     */
    public function logout()
    {
        // Destroy current customer session and log them out
        Auth::logout();
        
        // Redirect to login page after successful logout
        return redirect('/login');
    }
}
``` 
# STEP 6: web.php Routes  added:
routes/web.php
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerAuthController; // Import CustomerAuthController for customer authentication routes

/*
|-------------------------------------------------------------------------- 
| Web Routes - Customer Authentication System
|-------------------------------------------------------------------------- 
| These routes handle customer registration, login, logout and dashboard access
| Uses CustomerAuthController for authentication logic
|
*/

// Public routes - accessible without authentication
Route::get('/', fn () => view('layouts.app'));                    // Home page (landing page)
Route::get('/login', fn () => view('layouts.app'));               // Login page
Route::get('/register', fn () => view('layouts.app'));            // Registration page

// Protected route - requires authentication
Route::get('/dashboard', fn () => view('layouts.app'))->middleware('auth'); // Customer dashboard (only logged-in users)

/*
|-------------------------------------------------------------------------- 
| Authentication Actions (POST routes)
|-------------------------------------------------------------------------- 
*/

// Handle customer registration form submission
Route::post('/register', [CustomerAuthController::class, 'register']);

// Handle customer login form submission
Route::post('/login', [CustomerAuthController::class, 'login']);

// Handle customer logout
Route::post('/logout', [CustomerAuthController::class, 'logout']);

/*
|-------------------------------------------------------------------------- 
| ⚠️  DUPLICATE ROUTE - ISSUE DETECTED ⚠️
|-------------------------------------------------------------------------- 
| This route conflicts with the first Route::get('/', ...) above
| The second definition will override the first one
| RECOMMENDATION: Remove or comment out this duplicate
*/
Route::get('/', function () {
    return view('welcome'); // This will override layouts.app with welcome.blade.php
});
``` 

# STEP 7: Node modules install 
```php
npm install
``` 
# STEP 8: Vue install 
```php
npm install vue@3
``` 
# STEP 9: Laravel Vite Vue plugin install
```php
npm install @vitejs/plugin-vue --save-dev
``` 
# STEP 10: Create blad file for design and css in app.blade.php 
# resources/views/app.blade.php (ONLY ONE BLADE)
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Panel</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Vite -->
    @vite('resources/js/app.js')
    <script>
    window.authUser = @json(auth()->user());
</script>
</head>
<body class="bg-light">

    <!-- 🔥 ONLY THIS FOR VUE -->
    <div id="app"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
``` 
# STEP 11: Layout.vue file  create (Navbar + Bootstrap)
# resources/js/components/Layout.vue
```php
<template>
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
    <a class="navbar-brand" href="/">CustomerApp</a>

    <div class="ms-auto">
      <!-- Guest links -->
      <a v-if="path !== '/login'" href="/login" class="btn btn-outline-light me-2">
        Login
      </a>
      <a v-if="path !== '/register'" href="/register" class="btn btn-outline-light">
        Register
      </a>

      <!-- Logout (only dashboard) -->
      <form
        v-if="path === '/dashboard'"
        method="POST"
        action="/logout"
        class="d-inline"
      >
        <input type="hidden" name="_token" :value="csrf" />
        <button class="btn btn-danger ms-3">Logout</button>
      </form>
    </div>
  </nav>

  <!-- PAGE CONTENT -->
  <div class="container mt-5">
    <slot></slot>
  </div>
</template>

<script>
export default {
  props: ['path'],
  data() {
    return {
      csrf: document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute('content'),
    };
  },
};
</script>
``` 
# STEP 12: Dashboard.vue file create  (components folder)
# resources/js/components/Dashboard.vue
```php
<template>
  <Layout :path="path">
    <div class="card shadow text-center">
      <div class="card-body">
        <h2>
          Welcome, <span class="text-primary">{{ user.name }}</span> 🎉
        </h2>

        <p class="text-muted mt-2">
          You are logged in successfully
        </p>

        <form method="POST" action="/logout">
          <input type="hidden" name="_token" :value="csrf" />
          <button class="btn btn-danger mt-3">
            Logout
          </button>
        </form>
      </div>
    </div>
  </Layout>
</template>

<script>
import Layout from './Layout.vue';

export default {
  components: { Layout },
  data() {
    return {
      path: window.location.pathname,
      csrf: document.querySelector('meta[name="csrf-token"]').content,
      user: window.authUser, // 👈 Laravel se aaya hua user
    };
  },
};
</script>
``` 
# STEP 12:Register.vue  file create  (components folder)

# resources/js/components/Register.vue
```php
<template>
  <Layout :path="path">
    <div class="row justify-content-center">
      <div class="col-md-4">
        <div class="card shadow">
          <div class="card-body">
            <h4 class="text-center mb-3">Register</h4>

            <form method="POST" action="/register">
              <input type="hidden" name="_token" :value="csrf" />

              <input
                class="form-control mb-3"
                name="name"
                placeholder="Name"
                required
              />

              <input
                class="form-control mb-3"
                name="email"
                placeholder="Email"
                required
              />

              <input
                class="form-control mb-3"
                type="password"
                name="password"
                placeholder="Password"
                required
              />

              <button class="btn btn-success w-100">
                Register
              </button>
            </form>

          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script>
import Layout from './Layout.vue';

export default {
  components: { Layout },
  data() {
    return {
      path: window.location.pathname,
      csrf: document.querySelector('meta[name="csrf-token"]').content,
    };
  },
};
</script>
``` 
# STEP 13:Login.vue  file create  (components folder)
# resources/js/components/Login.vue
```php
<template>
  <Layout :path="path">
    <div class="row justify-content-center">
      <div class="col-md-4">
        <div class="card shadow">
          <div class="card-body">
            <h4 class="text-center mb-3">Register</h4>

            <form method="POST" action="/register">
              <input type="hidden" name="_token" :value="csrf" />

              <input
                class="form-control mb-3"
                name="name"
                placeholder="Name"
                required
              />

              <input
                class="form-control mb-3"
                name="email"
                placeholder="Email"
                required
              />

              <input
                class="form-control mb-3"
                type="password"
                name="password"
                placeholder="Password"
                required
              />

              <button class="btn btn-success w-100">
                Register
              </button>
            </form>

          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script>
import Layout from './Layout.vue';

export default {
  components: { Layout },
  data() {
    return {
      path: window.location.pathname,
      csrf: document.querySelector('meta[name="csrf-token"]').content,
    };
  },
};
</script>
``` 
# STEP 14 :Now Update for app.js file in  resource into js folder

# resources/js/app.js
```php
import { createApp } from 'vue';

import Login from './components/Login.vue';
import Register from './components/Register.vue';
import Dashboard from './components/Dashboard.vue';

createApp({
    components: {
        Login,
        Register,
        Dashboard,
    },
    data() {
        return {
            path: window.location.pathname,
        };
    },
    template: `
        <Login v-if="path === '/login'" />
        <Register v-else-if="path === '/register'" />
        <Dashboard v-else-if="path === '/dashboard'" />
        <Login v-else />
    `
}).mount('#app');
``` 
# STEP 15 :Now Update for Vite.Config.js file
```php
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
});
``` 
# Final and last step for now start server and followed url and run now
```php
php artisan optimize:clear
npm run build
npm run dev
php artisan serve
``` 
# Paste this Url in browser http://127.0.0.1:8000/register and fill all details ..

<img width="628" height="178" alt="image" src="https://github.com/user-attachments/assets/a6d6e963-6c3c-4bfd-9812-41f808726d3b" />
<img width="628" height="141" alt="image" src="https://github.com/user-attachments/assets/7dfb6249-eb9e-4aa8-a16f-1e5a42a5b393" />
<img width="628" height="132" alt="image" src="https://github.com/user-attachments/assets/f7ac9814-f0da-4dff-924d-3ca992ebc91e" />






