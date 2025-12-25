<?php
session_start();

// Check for error messages
if (isset($_GET['error'])) {
    $error_message = "";
    switch ($_GET['error']) {
        case 'order_failed':
            $error_message = "Failed to create order. Please try again.";
            break;
        case 'artwork_not_found':
            $error_message = "Artwork not found. Please select a valid artwork.";
            break;
        case 'invalid_parameters':
            $error_message = "Invalid parameters. Please try again.";
            break;
    }
    if (!empty($error_message)) {
        echo "<div class='error-message'>$error_message</div>";
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "
    <div class='checkout-container'>
        <div class='login-required'>
            <h2>Login Required</h2>
            <p>Please login to proceed with your purchase.</p>
            <p><a href='login.php' class='back-btn'>Login Now</a></p>
        </div>
    </div>";
    include('footer.html');
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "art";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user data from database
$user_id = $_SESSION['user_id'];
$user_email = '';
$user_first_name = '';
$user_last_name = '';

$user_sql = "SELECT first_name, last_name, email FROM register_data WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result && $user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $user_email = $user_data['email'];
    $user_first_name = $user_data['first_name'];
    $user_last_name = $user_data['last_name'];
} else {
    // Fallback values if user not found
    $user_email = 'user@example.com';
    $user_first_name = 'User';
    $user_last_name = '';
}
$user_stmt->close();

// Get artwork ID from GET parameter (from Buy Now) or POST (from cart)
$artwork_id = isset($_GET['artwork_id']) ? intval($_GET['artwork_id']) : 0;

// If no artwork_id in GET, check if it's coming from cart
if ($artwork_id == 0 && isset($_POST['artwork_id'])) {
    $artwork_id = intval($_POST['artwork_id']);
}

$artwork = null;
$artworks = []; // Array to handle multiple artworks in future

if ($artwork_id > 0) {
    // Fetch artwork details with artist information
    $sql = "SELECT a.*, ar.artist_name 
            FROM artworks a 
            JOIN artists ar ON a.artist_id = ar.artist_id 
            WHERE a.artwork_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $artwork_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $artwork = $result->fetch_assoc();
        $artworks[] = $artwork; // Store in array for consistent handling
    }
    $stmt->close();
}

// Calculate totals
$subtotal = 0;
if ($artwork) {
    $subtotal = $artwork['price'];
}
$tax = $subtotal * 0.05; // 5% GST
$shipping = 0; // Free shipping
$total = $subtotal + $tax + $shipping;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Art Gallery</title>
    <style>
        :root {
            --orange1: #f43a09;
            --orange2: #ffb766;
            --bluegreen: #c2edda;
            --green: #68d388;
            --dark: #2c2c54;
            --light: #f5f5f5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
            padding: 20px;
            min-height: 100vh;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            padding: 10px 20px;
            background: var(--dark);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .back-btn:hover {
            background: var(--orange1);
            transform: translateX(-5px);
        }

        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(44, 44, 84, 0.1);
        }

        .order-summary {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(44, 44, 84, 0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: var(--dark);
            border-bottom: 2px solid var(--bluegreen);
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--orange1);
        }

        .user-email {
            background-color: var(--light);
            padding: 12px;
            border-radius: 5px;
            border: 2px solid #ddd;
            font-weight: 500;
            color: var(--dark);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }

        .checkbox-group input {
            width: auto;
        }

        .shipping-method,
        .payment-method {
            background: rgba(194, 237, 218, 0.2);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .method-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .method-option.selected {
            border-color: var(--orange1);
            background-color: rgba(244, 58, 9, 0.05);
        }

        .method-option input {
            margin-right: 10px;
        }

        .method-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .method-price {
            font-weight: bold;
            color: var(--green);
        }

        .artwork-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .artwork-image {
            width: 80px;
            height: 80px;
            border-radius: 5px;
            overflow: hidden;
        }

        .artwork-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .artwork-details {
            flex: 1;
        }

        .artwork-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .artwork-artist {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .artwork-price {
            font-weight: bold;
            color: var(--orange1);
        }

        .price-breakdown {
            margin: 20px 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .price-row.total {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--dark);
            border-bottom: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid var(--bluegreen);
        }

        .discount-section {
            margin: 20px 0;
        }

        .discount-input {
            display: flex;
            gap: 10px;
        }

        .discount-input input {
            flex: 1;
        }

        .apply-btn {
            background: var(--dark);
            color: white;
            border: none;
            padding: 0 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .apply-btn:hover {
            background: var(--orange1);
        }

        .pay-now-btn {
            width: 100%;
            background: var(--orange1);
            color: white;
            border: none;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .pay-now-btn:hover {
            background: var(--dark);
            transform: translateY(-2px);
        }

        .secure-payment {
            text-align: center;
            margin-top: 15px;
            color: #666;
            font-size: 0.9rem;
        }

        .payment-icons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 10px 0;
        }

        .payment-icon {
            background: var(--light);
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .login-required {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(44, 44, 84, 0.1);
            grid-column: 1 / -1;
        }

        .login-required h2 {
            color: var(--orange1);
            margin-bottom: 20px;
        }

        .error-message {
            text-align: center;
            padding: 20px;
            background: rgba(244, 58, 9, 0.1);
            border-radius: 5px;
            margin: 20px 0;
        }

        .loading {
            color: #666;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .order-summary {
                position: static;
            }
        }
    </style>
</head>
<body>
    <?php include('header.php');?>
    <div class="checkout-container">
        <div class="checkout-form">
            <a href="artwork-details.php?id=<?php echo $artwork_id; ?>" class="back-btn">
                ← Back to Artwork
            </a>
            
            <h1 class="section-title">Contact</h1>
            
            <form id="checkoutForm" method="POST" action="payment-process.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="user-email" id="userEmail">
                        <?php echo htmlspecialchars($user_email); ?>
                    </div>
                    <input type="hidden" id="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>">
                    <small style="color: #666; font-size: 0.9rem;">Email cannot be changed for order communication</small>
                </div>

                <h1 class="section-title">Delivery</h1>
                
                <div class="form-group">
                    <label for="country">Country/Region *</label>
                    <select id="country" name="country" required>
                        <option value="">Select Country</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First name *</label>
                        <input type="text" id="first_name" name="first_name" required 
                               placeholder="Enter your first name" value="<?php echo htmlspecialchars($user_first_name); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last name *</label>
                        <input type="text" id="last_name" name="last_name" required 
                               placeholder="Enter your last name" value="<?php echo htmlspecialchars($user_last_name); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="company">Company (optional)</label>
                    <input type="text" id="company" name="company" placeholder="Optional">
                </div>

                <div class="form-group">
                    <label for="address">Address *</label>
                    <input type="text" id="address" name="address" required 
                           placeholder="Street address">
                </div>

                <div class="form-group">
                    <label for="address2">Apartment, suite, etc. (optional)</label>
                    <input type="text" id="address2" name="address2" placeholder="Optional">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City *</label>
                        <select id="city" name="city" required>
                            <option value="">Select City</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="state">State/Province *</label>
                        <select id="state" name="state" required>
                            <option value="">Select State</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="pin_code">ZIP/Postal Code *</label>
                        <input type="text" id="pin_code" name="pin_code" required 
                               placeholder="Enter postal code">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="tel" id="phone" name="phone" required 
                               placeholder="Enter phone number">
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="save_address" name="save_address" checked>
                    <label for="save_address">Save this information for next time</label>
                </div>

                <h1 class="section-title">Shipping method</h1>
                
                <div class="shipping-method">
                    <div class="method-option selected" onclick="selectShipping('standard')">
                        <div class="method-info">
                            <input type="radio" name="shipping" value="standard" checked>
                            <span>Standard Shipping</span>
                        </div>
                        <div class="method-price" id="shippingPrice">FREE</div>
                    </div>
                </div>

                <h1 class="section-title">Payment</h1>
                
                <div class="payment-method">
                    <div class="secure-payment">
                        All transactions are secure and encrypted.
                    </div>
                    
                    <div class="method-option selected">
                        <div class="method-info">
                            <input type="radio" name="payment" value="online" checked>
                            <span>Online Payment (Cards, UPI, Net Banking)</span>
                        </div>
                    </div>
                    
                    <div class="payment-icons">
                        <div class="payment-icon">VISA</div>
                        <div class="payment-icon">MasterCard</div>
                        <div class="payment-icon">UPI</div>
                        <div class="payment-icon">NetBanking</div>
                    </div>
                    
                    <div class="secure-payment">
                        After clicking "Pay now", you will be redirected to complete your purchase securely.
                    </div>
                </div>

                <input type="hidden" name="artwork_id" value="<?php echo $artwork_id; ?>">
                <input type="hidden" name="total_amount" id="total_amount" value="<?php echo number_format($total, 2); ?>">

                <button type="submit" class="pay-now-btn">
                    Pay Now - Rs.<span id="displayTotal"><?php 
                        echo isset($artwork['price']) ? number_format($artwork['price'] * 1.05, 2) : '0.00';
                    ?></span>
                </button>
            </form>
        </div>

        <div class="order-summary">
            <h2 class="section-title">Order Summary</h2>
            
            <?php if ($artwork): ?>
                <div class='artwork-item'>
                    <div class='artwork-image'>
                        <?php
                        $image_url = $artwork['image_url'];
                        $imageDisplay = "";
                        if (!empty($image_url)) {
                            $possible_paths = [
                                $image_url,
                                "images/" . $image_url,
                                "./" . $image_url,
                                "./images/" . basename($image_url),
                                basename($image_url)
                            ];
                            
                            $found_path = null;
                            foreach ($possible_paths as $test_path) {
                                $clean_path = str_replace('\\', '/', $test_path);
                                if (file_exists($clean_path)) {
                                    $found_path = $clean_path;
                                    break;
                                }
                            }
                            
                            if ($found_path) {
                                $imageDisplay = "<img src='{$found_path}' alt='{$artwork['title']}' />";
                            } else {
                                $imageDisplay = "<div style='width:80px;height:80px;background:var(--bluegreen);display:flex;align-items:center;justify-content:center;border-radius:5px;'>Image</div>";
                            }
                        } else {
                            $imageDisplay = "<div style='width:80px;height:80px;background:var(--bluegreen);display:flex;align-items:center;justify-content:center;border-radius:5px;'>Art</div>";
                        }
                        echo $imageDisplay;
                        ?>
                    </div>
                    <div class='artwork-details'>
                        <div class='artwork-title'><?php echo htmlspecialchars($artwork['title']); ?></div>
                        <div class='artwork-artist'>by <?php echo htmlspecialchars($artwork['artist_name']); ?></div>
                        <div class='artwork-price'>Rs.<?php echo number_format($artwork['price'], 2); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="error-message">
                    <p>No artwork selected. Please go back and select an artwork.</p>
                    <a href="artgallary.php" class="back-btn">Browse Artworks</a>
                </div>
            <?php endif; ?>

            <div class="price-breakdown">
                <div class="price-row">
                    <span>Subtotal</span>
                    <span id="subtotal">Rs.<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="price-row">
                    <span>Shipping</span>
                    <span id="shippingCost">FREE</span>
                </div>
                <div class="price-row">
                    <span>Estimated taxes (5% GST)</span>
                    <span id="taxAmount">Rs.<?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="price-row total">
                    <span>Total</span>
                    <span id="totalAmount">Rs.<?php echo number_format($total, 2); ?></span>
                </div>
            </div>

        </div>
    </div>

 <script>
    // Country, State, City data
    const locationData = {
        "India": {
            states: {
                "Gujarat": ["Ahmedabad", "Surat", "Vadodara", "Rajkot", "Bhavnagar", "Jamnagar"],
                "Maharashtra": ["Mumbai", "Pune", "Nagpur", "Thane", "Nashik", "Aurangabad"],
                "Delhi": ["New Delhi", "Delhi Cantonment", "Narela"],
                "Karnataka": ["Bangalore", "Mysore", "Hubli", "Belgaum", "Mangalore"],
                "Tamil Nadu": ["Chennai", "Coimbatore", "Madurai", "Tiruchirappalli"],
                "Bihar": ["Patna", "Gaya", "Bhagalpur", "Muzaffarpur", "Darbhanga"],
                "Uttar Pradesh": ["Lucknow", "Kanpur", "Varanasi", "Agra", "Prayagraj"],
                "Rajasthan": ["Jaipur", "Jodhpur", "Udaipur", "Kota", "Bikaner"]
            }
        },
        "United States": {
            states: {
                "California": ["Los Angeles", "San Francisco", "San Diego", "San Jose", "Sacramento"],
                "New York": ["New York City", "Buffalo", "Rochester", "Albany", "Syracuse"],
                "Texas": ["Houston", "Dallas", "Austin", "San Antonio", "Fort Worth"]
            }
        },
        "United Kingdom": {
            states: {
                "England": ["London", "Manchester", "Birmingham", "Liverpool", "Leeds"],
                "Scotland": ["Edinburgh", "Glasgow", "Aberdeen", "Dundee", "Inverness"],
                "Wales": ["Cardiff", "Swansea", "Newport", "Bangor"]
            }
        },
        "Canada": {
            states: {
                "Ontario": ["Toronto", "Ottawa", "Mississauga", "Hamilton", "London"],
                "Quebec": ["Montreal", "Quebec City", "Laval", "Gatineau"],
                "British Columbia": ["Vancouver", "Victoria", "Surrey", "Burnaby"]
            }
        },
        "Australia": {
            states: {
                "New South Wales": ["Sydney", "Newcastle", "Wollongong", "Central Coast"],
                "Victoria": ["Melbourne", "Geelong", "Ballarat", "Bendigo"],
                "Queensland": ["Brisbane", "Gold Coast", "Sunshine Coast", "Cairns"]
            }
        }
    };

    // Price calculations
    const subtotal = <?php echo $subtotal; ?>;
    const taxRate = 0.05;
    const taxAmount = subtotal * taxRate;
    let shippingCost = 0;
    let discountAmount = 0;
    let totalAmount = subtotal + taxAmount + shippingCost;

    // Initialize countries dropdown
    function initializeCountries() {
        const countrySelect = document.getElementById('country');
        const countries = Object.keys(locationData);
        
        countries.forEach(country => {
            const option = document.createElement('option');
            option.value = country;
            option.textContent = country;
            countrySelect.appendChild(option);
        });
        
        // Set default country to India
        countrySelect.value = 'India';
        loadStates();
        updateShipping();
    }

    // Update states based on selected country
    function loadStates() {
        const country = document.getElementById('country').value;
        const stateSelect = document.getElementById('state');
        const citySelect = document.getElementById('city');
        
        stateSelect.innerHTML = '<option value="">Select State</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';
        
        if (country && locationData[country]) {
            const states = Object.keys(locationData[country].states);
            states.forEach(state => {
                const option = document.createElement('option');
                option.value = state;
                option.textContent = state;
                stateSelect.appendChild(option);
            });
        }
    }

    // Update cities based on selected state
    function loadCities() {
        const country = document.getElementById('country').value;
        const state = document.getElementById('state').value;
        const citySelect = document.getElementById('city');
        
        citySelect.innerHTML = '<option value="">Select City</option>';
        
        if (country && state && locationData[country] && locationData[country].states[state]) {
            const cities = locationData[country].states[state];
            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
        }
    }
        function updateShipping() {
        const country = document.getElementById('country').value;
        const shippingPrice = document.getElementById('shippingPrice');
        const shippingCostElement = document.getElementById('shippingCost');
        
        // Get the selected shipping method
        const selectedShipping = document.querySelector('input[name="shipping"]:checked');
        const shippingMethod = selectedShipping ? selectedShipping.value : 'standard';
        
        if (country === 'India') {
            if (shippingMethod === 'express') {
                shippingCost = 200;
                shippingPrice.textContent = '₹200';
                shippingCostElement.textContent = '₹200';
            } else {
                shippingCost = 0;
                shippingPrice.textContent = 'FREE';
                shippingCostElement.textContent = 'FREE';
            }
        } else if (country) {
            // International shipping
            if (shippingMethod === 'express') {
                shippingCost = 1245; // ₹1,245 for international express
                shippingPrice.textContent = '₹1,245';
                shippingCostElement.textContent = '₹1,245';
            } else {
                shippingCost = 560; // ₹560 for international standard
                shippingPrice.textContent = '₹560';
                shippingCostElement.textContent = '₹560';
            }
        } else {
            shippingCost = 0;
            shippingPrice.textContent = '--';
            shippingCostElement.textContent = '--';
        }
        
        updateTotal();
    }

    // Shipping method selection - UPDATED
    function selectShipping(method) {
        document.querySelectorAll('.method-option').forEach(option => {
            option.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        
        const radio = event.currentTarget.querySelector('input[type="radio"]');
        radio.checked = true;
        
        // Update shipping based on country and method
        updateShipping();
    }


    // Shipping calculation - RESTORED INTERNATIONAL PRICING
    function updateShipping() {
        const country = document.getElementById('country').value;
        const shippingPrice = document.getElementById('shippingPrice');
        const shippingCostElement = document.getElementById('shippingCost');
        
        if (country === 'India') {
            shippingCost = 0;
            shippingPrice.textContent = 'FREE';
            shippingCostElement.textContent = 'FREE';
        } else if (country) {
            // International shipping - $6.76 USD converted to INR
            shippingCost = 6.76 * 83; // Convert USD to INR (83 INR per USD)
            shippingPrice.textContent = '₹560';
            shippingCostElement.textContent = '₹560';
        } else {
            shippingCost = 0;
            shippingPrice.textContent = '--';
            shippingCostElement.textContent = '--';
        }
        
        updateTotal();
    }

    // Shipping method selection
    function selectShipping(method) {
        document.querySelectorAll('.method-option').forEach(option => {
            option.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        
        const radio = event.currentTarget.querySelector('input[type="radio"]');
        radio.checked = true;
        
        // Update shipping based on selection
        const country = document.getElementById('country').value;
        if (method === 'express') {
            if (country === 'India') {
                shippingCost = 200;
                document.getElementById('shippingPrice').textContent = '₹200';
                document.getElementById('shippingCost').textContent = '₹200';
            } else {
                // International express shipping - $15 USD converted to INR
                shippingCost = 15 * 83;
                document.getElementById('shippingPrice').textContent = '₹1,245';
                document.getElementById('shippingCost').textContent = '₹1,245';
            }
        } else {
            if (country === 'India') {
                shippingCost = 0;
                document.getElementById('shippingPrice').textContent = 'FREE';
                document.getElementById('shippingCost').textContent = 'FREE';
            } else {
                // International standard shipping - $6.76 USD converted to INR
                shippingCost = 6.76 * 83;
                document.getElementById('shippingPrice').textContent = '₹560';
                document.getElementById('shippingCost').textContent = '₹560';
            }
        }
        
        updateTotal();
    }

    // Calculate total amount
    function calculateTotal() {
        totalAmount = subtotal + taxAmount + shippingCost - discountAmount;
    }

    // Update all price displays
    function updateTotal() {
        calculateTotal();
        
        // Update breakdown
        document.getElementById('subtotal').textContent = 'Rs.' + subtotal.toFixed(2);
        document.getElementById('taxAmount').textContent = 'Rs.' + taxAmount.toFixed(2);
        document.getElementById('totalAmount').textContent = 'Rs.' + totalAmount.toFixed(2);
        
        // Update payment button
        document.getElementById('displayTotal').textContent = totalAmount.toFixed(2);
        
        // Update hidden form field
        document.getElementById('total_amount').value = totalAmount.toFixed(2);
    }

    // Discount application
    function applyDiscount() {
        const discountCode = document.getElementById('discount_code').value;
        const discountMessage = document.getElementById('discountMessage');
        
        if (!discountCode) {
            discountMessage.textContent = 'Please enter a discount code';
            discountMessage.style.color = 'var(--orange1)';
            return;
        }
        
        // Simulate discount validation
        const validDiscounts = {
            'ART10': 0.1,
            'WELCOME5': 0.05,
            'FIRSTORDER': 0.15
        };
        
        if (validDiscounts[discountCode.toUpperCase()]) {
            const discountRate = validDiscounts[discountCode.toUpperCase()];
            discountAmount = subtotal * discountRate;
            
            // Create or update discount row
            let discountRow = document.getElementById('discountRow');
            if (!discountRow) {
                discountRow = document.createElement('div');
                discountRow.id = 'discountRow';
                discountRow.className = 'price-row';
                discountRow.innerHTML = '<span>Discount</span><span style="color: var(--green);">-Rs.' + discountAmount.toFixed(2) + '</span>';
                document.querySelector('.price-breakdown').insertBefore(discountRow, document.querySelector('.price-row.total'));
            } else {
                discountRow.innerHTML = '<span>Discount</span><span style="color: var(--green);">-Rs.' + discountAmount.toFixed(2) + '</span>';
            }
            
            discountMessage.textContent = 'Discount applied successfully!';
            discountMessage.style.color = 'var(--green)';
            
            updateTotal();
            
        } else {
            discountAmount = 0;
            const discountRow = document.getElementById('discountRow');
            if (discountRow) {
                discountRow.remove();
            }
            
            discountMessage.textContent = 'Invalid discount code';
            discountMessage.style.color = 'var(--orange1)';
            
            updateTotal();
        }
    }

    // Form validation
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const requiredFields = [
            'first_name', 'last_name', 'address', 'country', 
            'state', 'city', 'pin_code', 'phone'
        ];
        
        for (const fieldName of requiredFields) {
            const field = document.getElementById(fieldName);
            if (!field.value.trim()) {
                e.preventDefault();
                alert(`Please fill in the ${field.labels[0].textContent} field.`);
                field.focus();
                return;
            }
        }
        
        // Validate phone number
        const phone = document.getElementById('phone').value;
        const phoneRegex = /^[6-9]\d{9}$/;
        if (!phoneRegex.test(phone.replace(/\D/g, ''))) {
            e.preventDefault();
            alert('Please enter a valid 10-digit Indian phone number.');
            document.getElementById('phone').focus();
            return;
        }
        
        // Validate PIN code
        const pinCode = document.getElementById('pin_code').value;
        const pinRegex = /^\d{6}$/;
        if (!pinRegex.test(pinCode)) {
            e.preventDefault();
            alert('Please enter a valid 6-digit PIN code.');
            document.getElementById('pin_code').focus();
            return;
        }
    });

    // Enter key support for discount code
    document.getElementById('discount_code')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyDiscount();
        }
    });

    // Real-time form validation
    document.querySelectorAll('input[required], select[required]').forEach(input => {
        input.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.style.borderColor = 'var(--orange1)';
            } else {
                this.style.borderColor = '#ddd';
            }
        });
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initializeCountries();
        
        // Add event listeners for country and state changes
        document.getElementById('country').addEventListener('change', function() {
            loadStates();
            updateShipping();
        });
        
        document.getElementById('state').addEventListener('change', loadCities);
    });
</script>
    
    <?php include('footer.html'); ?>
</body>
</html>