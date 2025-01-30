<?php

// Utils
require_once __DIR__ . '/Utils/Validator.php';

// Models
require_once __DIR__ . '/Models/User.php';

// Config
require_once __DIR__ . '/Config/JWTConfig.php';
require_once __DIR__ . '/Config/Database.php';

// Controllers
require_once __DIR__ . '/Controllers/HomeController.php';
require_once __DIR__ . '/Controllers/StatusController.php';
require_once __DIR__ . '/Controllers/AuthController.php';

// Middleware
require_once __DIR__ . '/Middleware/RateLimitMiddleware.php';

// Services
require_once __DIR__ . '/Services/LoggerService.php';
require_once __DIR__ . '/Services/AuthService.php';

// Routes
require_once __DIR__ . '/Routes/Router.php';

// Dotenv
require_once __DIR__ . '/vendor/autoload.php';