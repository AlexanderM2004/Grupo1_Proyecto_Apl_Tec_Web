<?php

// Utils
require_once __DIR__ . '/utils/Validator.php';

// Models
require_once __DIR__ . '/models/User.php';

// Config
require_once __DIR__ . '/config/JWTConfig.php';
require_once __DIR__ . '/config/Database.php';

// Controllers
require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/StatusController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ProfileController.php';

// Middleware
require_once __DIR__ . '/middleware/RateLimitMiddleware.php';

// Services
require_once __DIR__ . '/services/LoggerService.php';
require_once __DIR__ . '/services/AuthService.php';

// Routes
require_once __DIR__ . '/routes/Router.php';

// Dotenv
require_once __DIR__ . '/vendor/autoload.php';