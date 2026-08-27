<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/system_functions.php';

// Get login image if available
$login_image = getSystemSetting($conn, 'login_image', '');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        \App\Core\Csrf::validate($_POST['csrf_token'] ?? null);

        // Validate input
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $purok_id = intval($_POST['purok_id']); // new purok field

        if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($address) || empty($purok_id)) {
            throw new Exception("All fields including purok are required");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Validate password match
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match!");
        }

        // Validate password strength
        if (strlen($_POST['password']) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        // Hash password
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Prepare phone number - ensure it's stored with +63 prefix
        $phone = preg_replace('/^\+63/', '', $phone); // Remove +63 if present
        $phone = preg_replace('/^0/', '', $phone); // Remove leading 0 if present
        $phone = '+63' . $phone; // Add +63 prefix

        // Validate phone format (should be +63 followed by 10 digits)
        if (!preg_match('/^\+639\d{9}$/', $phone)) {
            throw new Exception("Invalid phone number format. Must be 10 digits starting with 9");
        }

        // Check if email already exists
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt === false) {
            throw new Exception("Database error: " . $conn->error);
        }

        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            throw new Exception("Email address already registered");
        }
        $check_stmt->close();

        // Insert into users table with purok_id
        $sql = "INSERT INTO users (first_name, last_name, email, phone, address, password, role, purok_id) 
                VALUES (?, ?, ?, ?, ?, ?, 'resident', ?)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $phone, $address, $password, $purok_id);

        if ($stmt->execute()) {
            header("Location: login.php?registration=success");
            exit();
        } else {
            throw new Exception("Registration failed: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Registration error: " . $error);
    }
}

// Fetch puroks for dropdown
$purok_result = $conn->query("SELECT purok_id, name FROM puroks ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BoteBank Registration</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script type="module" src="assets/js/app/theme.js"></script>
  <style>
    .input-group {
      position: relative;
    }
    .input-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      pointer-events: none;
    }
    .input-with-icon {
      padding-left: 42px;
    }
    .strength-meter {
      height: 4px;
      border-radius: 2px;
      transition: all 0.3s ease;
    }
  </style>
</head>
<body class="bg-bote-stone-50 font-sans">
  <div class="min-h-screen flex">
    <!-- Left Side -->
    <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
      <?php if ($login_image): ?>
        <!-- Custom login image -->
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('uploads/system/auth/<?php echo htmlspecialchars($login_image); ?>');">
          <div class="absolute inset-0 bg-black bg-opacity-30"></div>
        </div>
      <?php else: ?>
        <!-- Default gradient background -->
        <div class="absolute inset-0 bg-gradient-to-br from-bote-info-600 to-bote-green-700"></div>
      <?php endif; ?>
      
      <div class="absolute inset-0 bg-black bg-opacity-20"></div>
      <div class="relative z-10 flex flex-col justify-center items-center text-white p-12">
        <svg class="w-24 h-24 text-white mb-6" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
        </svg>
        <h1 class="text-4xl font-bold mb-4">BoteBank</h1>
        <p class="text-lg text-bote-info-100 text-center">Join our community and start earning points by recycling!</p>
      </div>
    </div>

    <!-- Right Side -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-4 lg:p-6">
      <div class="max-w-xl w-full space-y-6 bg-white p-6 lg:p-8 rounded-xl shadow">
        <div class="text-center">
          <h2 class="text-3xl font-bold text-bote-stone-900 mb-2">Create an Account</h2>
          <p class="text-bote-stone-600">Register to start your recycling journey</p>
        </div>

        <?php if (isset($error)): ?>
          <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              <p class="text-red-700 font-medium"><?php echo htmlspecialchars($error); ?></p>
            </div>
          </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="space-y-5" id="registrationForm">
          <?php echo \App\Core\Csrf::field(); ?>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- First Name -->
            <div class="form-group">
              <label class="block mb-2 text-sm font-semibold text-bote-stone-700">
                First Name <span class="text-red-500">*</span>
              </label>
              <div class="input-group">
                <div class="input-icon">
                  <svg class="w-5 h-5 text-bote-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                </div>
                <input type="text" name="first_name" required 
                  class="w-full input-with-icon py-3 text-base border-2 border-bote-stone-200 rounded-lg focus:border-bote-green-500 focus:ring-0 transition-colors"
                  placeholder="Juan">
              </div>
            </div>
            <!-- Last Name -->
            <div class="form-group">
              <label class="block mb-2 text-sm font-semibold text-bote-stone-700">
                Last Name <span class="text-red-500">*</span>
              </label>
              <div class="input-group">
                <div class="input-icon">
                  <svg class="w-5 h-5 text-bote-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                </div>
                <input type="text" name="last_name" required 
                  class="w-full input-with-icon py-3 text-base border-2 border-bote-stone-200 rounded-lg focus:border-bote-green-500 focus:ring-0 transition-colors"
                  placeholder="Dela Cruz">
              </div>
            </div>
            <!-- Email -->
            <div class="form-group">
              <label class="block mb-2 text-sm font-semibold text-bote-stone-700">
                Email Address <span class="text-red-500">*</span>
              </label>
              <div class="input-group">
                <div class="input-icon">
                  <svg class="w-5 h-5 text-bote-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                </div>
                <input type="email" name="email" required 
                  class="w-full input-with-icon py-3 text-base border-2 border-bote-stone-200 rounded-lg focus:border-bote-green-500 focus:ring-0 transition-colors"
                  placeholder="juan.delacruz@example.com">
              </div>
            </div>
            <!-- Phone -->
            <div class="form-group">
              <label class="block mb-2 text-sm font-semibold text-bote-stone-700">
                Phone Number <span class="text-red-500">*</span>
              </label>
              <div class="flex items-center gap-2">
                <div class="flex items-center gap-2 px-3 py-3 border-2 border-bote-stone-200 bg-bote-stone-50 rounded-lg">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" class="w-7 h-5 flex-shrink-0">
                    <path fill="#fff" d="M0 0h213.3v480H0z"/>
                    <path fill="#0038a8" d="M213.3 0H640v240H213.3z"/>
                    <path fill="#ce1126" d="M213.3 240H640v240H213.3z"/>
                    <g transform="translate(106.7,240) scale(3.2)">
                      <circle r="20" fill="none" stroke="#fcd116" stroke-width="2"/>
                      <circle r="12" fill="#fcd116"/>
                    </g>
                  </svg>
                  <span class="text-bote-stone-700 font-medium">+63</span>
                </div>
                <div class="flex-1 input-group">
                  <div class="input-icon">
                    <svg class="w-5 h-5 text-bote-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                  </div>
                  <input type="tel" name="phone" id="phoneInput" required pattern="9\d{9}" maxlength="10" 
                    class="w-full input-with-icon py-3 text-base border-2 border-bote-stone-200 rounded-lg focus:border-bote-green-500 focus:ring-0 transition-colors"
                    placeholder="9123456789">
                </div>
              </div>
              <p class="mt-2 text-xs text-bote-stone-500 flex items-center gap-1">
                <svg class="w-4 h-4 text-bote-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <span>Stored as: <strong>+639XXXXXXXXX</strong> (13 characters)</span>
              </p>
            </div>

            <!-- Address -->
            <div class="md:col-span-1">
              <label class="block mb-2 text-sm font-semibold text-bote-stone-700">
                Street Address <span class="text-red-500">*</span>
              </label>
              <div class="input-group">
                <div class="input-icon">
                  <svg class="w-5 h-5 text-bote-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                </div>
                <input type="text" name="address" required
                  class="w-full input-with-icon py-3 text-base border-2 border-bote-stone-200 rounded-lg focus:border-bote-green-500 focus:ring-0 transition-colors"
                  placeholder="123 Mabini Street">
              </div>
            </div>
            <!-- Purok -->
            <div class="md:col-span-1">
              <label class="block mb-2 text-sm font-semibold text-bote-stone-700">
                Purok <span class="text-red-500">*</span>
              </label>
              <div class="input-group">
                <div class="input-icon">
                  <svg class="w-5 h-5 text-bote-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                  </svg>
                </div>
                <select name="purok_id" required 
                  class="w-full input-with-icon py-3 text-base border-2 border-bote-stone-200 rounded-lg focus:border-bote-green-500 focus:ring-0 transition-colors appearance-none bg-white cursor-pointer">
                  <option value="">-- Select Your Purok --</option>
                  <?php while ($row = $purok_result->fetch_assoc()): ?>
                    <option value="<?= e($row['purok_id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                  <?php endwhile; ?>
                </select>
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
                  <svg class="w-5 h-5 text-bote-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                  </svg>
                </div>
              </div>
            </div>
            <!-- Password -->
            <div class="form-group">
              <label class="block mb-2 text-sm font-semibold text-bote-stone-700">
                Password <span class="text-red-500">*</span>
              </label>
              <div class="input-group relative">
                <div class="input-icon">
                  <svg class="w-5 h-5 text-bote-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                  </svg>
                </div>
                <input type="password" name="password" id="password" required minlength="8"
                  class="w-full input-with-icon py-3 pr-10 text-base border-2 border-bote-stone-200 rounded-lg focus:border-bote-green-500 focus:ring-0 transition-colors"
                  placeholder="Minimum 8 characters">
                <button type="button" onclick="togglePassword('password', 'eyeIcon1')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-bote-stone-400 hover:text-bote-stone-600">
                  <svg id="eyeIcon1" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </button>
              </div>
              <div id="passwordStrength" class="mt-2">
                <div class="strength-meter bg-bote-stone-200 w-full"></div>
                <p class="text-xs text-bote-stone-500 mt-1">Must be at least 8 characters long</p>
              </div>
            </div>
            <!-- Confirm Password -->
            <div class="form-group">
              <label class="block mb-2 text-sm font-semibold text-bote-stone-700">
                Confirm Password <span class="text-red-500">*</span>
              </label>
              <div class="input-group relative">
                <div class="input-icon">
                  <svg class="w-5 h-5 text-bote-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <input type="password" name="confirm_password" id="confirm_password" required minlength="8"
                  class="w-full input-with-icon py-3 pr-10 text-base border-2 border-bote-stone-200 rounded-lg focus:border-bote-green-500 focus:ring-0 transition-colors"
                  placeholder="Re-enter password">
                <button type="button" onclick="togglePassword('confirm_password', 'eyeIcon2')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-bote-stone-400 hover:text-bote-stone-600">
                  <svg id="eyeIcon2" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </button>
              </div>
              <p id="passwordMatch" class="mt-2 text-xs text-bote-stone-500"></p>
            </div>

          </div>

          <!-- Terms -->
          <div class="flex items-start pt-2">
            <div class="flex items-center h-5">
              <input type="checkbox" id="agree_terms" name="agree_terms" required 
                class="w-4 h-4 text-bote-green-600 border-bote-stone-300 rounded focus:ring-bote-green-500">
            </div>
            <label for="agree_terms" class="ml-3 text-sm text-bote-stone-700">
              I agree to the <a href="#" class="text-bote-green-600 hover:text-bote-green-700 font-semibold">Terms and Conditions</a> 
              and <a href="#" class="text-bote-green-600 hover:text-bote-green-700 font-semibold">Privacy Policy</a>
            </label>
          </div>

          <!-- Submit -->
          <button type="submit" id="submitBtn" 
            class="w-full py-3 px-4 text-base font-semibold text-white rounded-lg shadow-md bg-gradient-to-r from-bote-green-600 to-bote-green-700 hover:from-bote-green-700 hover:to-bote-green-800 transition-all transform hover:scale-[1.01] flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            <span>Create Account</span>
          </button>

          <!-- Already Registered / Back to Home Links -->
          <div class="grid grid-cols-2 gap-4 pt-2">
            <p class="text-center text-sm text-bote-stone-600">
              <a href="login.php" class="font-semibold text-bote-info-600 hover:text-bote-info-500">Already have an account?</a>
            </p>
            <p class="text-center text-sm text-bote-stone-600">
              <a href="index.php" class="font-semibold text-bote-info-600 hover:text-bote-info-500">← Back to Home</a>
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script type="module" src="assets/js/pages/public/register.js"></script>
</body>
</html>
