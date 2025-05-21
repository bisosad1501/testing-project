<?php
/**
 * Main app
 */
class App
{
    protected $router;
    protected $controller;
    protected $plugins;

    // An array of the URL routes
    protected static $routes = [];


    /**
     * summary
     */
    public function __construct()
    {
        $this->controller = new Controller;
    }


    /**
     * Adds a new route to the App:$routes static variable
     * App::$routes will be mapped on a route
     * initializes on App initializes
     *
     * Format: ["METHOD", "/uri/", "Controller"]
     * Example: App:addRoute("GET|POST", "/post/?", "Post");
     */
    public static function addRoute()
    {
        $route = func_get_args();
        if ($route) {
            self::$routes[] = $route;
        }
    }


    /**
     * Get App::$routes
     * @return array An array of the added routes
     */
    public static function getRoutes()
    {
        return self::$routes;
    }


    /**
     * Get IP info
     * @return stdClass
     */
    private function ipinfo()
    {
        $client = empty($_SERVER['HTTP_CLIENT_IP'])
                ? null : $_SERVER['HTTP_CLIENT_IP'];
        $forward = empty($_SERVER['HTTP_X_FORWARDED_FOR'])
                 ? null : $_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = empty($_SERVER['REMOTE_ADDR'])
                ? null : $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } else if (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }


        if (!isset($_SESSION[$ip])) {
            $res = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip), true);

            $ipinfo = [
                "request" => "", // Requested Ip Address
                "status" => "", // Status code (200 for success)
                "credit" => "",
                "city" => "",
                "region" => "",
                "areaCode" => "",
                "dmaCode" => "",
                "countryCode" => "",
                "countryName" => "",
                "continentCode" => "",
                "latitude" => "",
                "longitude" => "",
                "regionCode" => "",
                "regionName" => "",
                "currencyCode" => "",
                "currencySymbol" => "",
                "currencySymbol_UTF8" => "",
                "currencyConverter" => "",
                "timezone" => "", // Will be used only in registration
                                  // process to detect user's
                                  // timezone automatically
                "neighbours" => [], // Neighbour country codes (ISO 3166-1 alpha-2)
                "languages" => [] // Spoken languages in the country
                                  // Will be user to auto-detect user language
            ];
            if (is_array($res)) {
                foreach ($res as $key => $value) {
                    $key = explode("_", $key, 2);
                    if (isset($key[1])) {
                        $ipinfo[$key[1]] = $value;
                    }
                }
            }

            if ($ipinfo["latitude"] && $ipinfo["longitude"]) {
                $Settings = Controller::model("GeneralData", "settings");
                $username = $Settings->get("data.geonamesorg_username");

                if ($username) {
                    // Get timezone
                    if (!empty($ipinfo["latitude"]) && !empty($ipinfo["longitude"])) {
                        $res = @json_decode(file_get_contents("http://api.geonames.org/timezoneJSON?lat=".$ipinfo["latitude"]."&lng=".$ipinfo["longitude"]."&username=".$username));

                        if (isset($res->timezoneId)) {
                            $ipinfo["timezone"] = $res->timezoneId;
                        }
                    }


                    // Get neighbours
                    if (!empty($ipinfo["countryCode"])) {
                        $res = @json_decode(file_get_contents("http://api.geonames.org/neighboursJSON?country=".$ipinfo["countryCode"]."&username=".$username));

                        if (!empty($res->geonames)) {
                            foreach ($res->geonames as $r) {
                                $ipinfo["neighbours"][] = $r->countryCode;
                            }
                        }
                    }

                    // Get country
                    if (!empty($ipinfo["countryCode"])) {
                        $res = @json_decode(file_get_contents("http://api.geonames.org/countryInfoJSON?country=".$ipinfo["countryCode"]."&username=".$username));

                        if (!empty($res->geonames[0]->languages)) {
                            $langs = explode(",", $res->geonames[0]->languages);
                            foreach ($langs as $l) {
                                $ipinfo["languages"][] = $l;
                            }
                        }
                    }
                }
            }

            $_SESSION[$ip] = $ipinfo;
        }

        return json_decode(json_encode($_SESSION[$ip]));
    }


    /**
     * Create database connection
     * @return App
     */
    private function db()
    {
        $config = [
            'driver' => 'mysql',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASS,
            'charset' => DB_ENCODING,
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        ];

        new \Pixie\Connection('mysql', $config, 'DB');
        return $this;
    }


    /**
     * Check and get authorized user data
     * Define $AuthUser variable
     */
    // private function auth()
    // {
    //     $AuthUser = null;
    //     if (Input::cookie("nplh")) {
    //         $hash = explode(".", Input::cookie("nplh"), 2);

    //         if (count($hash) == 2) {
    //             $User = Controller::Model("User", (int)$hash[0]);

    //             if ($User->isAvailable() &&
    //                 $User->get("is_active") == 1 &&
    //                 md5($User->get("password")) == $hash[1])
    //             {
    //                 $AuthUser = $User;

    //                 if (Input::cookie("nplrmm")) {
    //                     setcookie("nplh", $User->get("id").".".md5($User->get("password")), time()+86400*30, "/");
    //                     setcookie("nplrmm", "1", time()+86400*30, "/");
    //                 }
    //             }
    //         }
    //     }

    //     return $AuthUser;
    // }

    /**
     * @author Phong-Kaster
     * @since 15-10-2022
     *
     * If keyword = Patient then it is a PATIENT are trying to logging
     * If keyword = null then it is a DOCTOR are trying to logging
     *
     * Check and get authorized user data
     * Define $AuthUser variable
     */
    private function auth()
    {
        $AuthUser = null;
        $Authorization = null;

        // Sử dụng getallheaders() nếu có sẵn, nếu không thì sử dụng apache_request_headers()
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            // Fallback nếu cả hai hàm đều không có sẵn
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (substr($key, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
                }
            }
        }

        // Debug: Log all headers
        error_log("All headers: " . print_r($headers, true));

        /**Step 2 - what is type of logging request */
        $keyword = "Doctor";//default
        if(isset($headers['type']))
        {
            $keyword = $headers['type'] ? $headers['type'] : "Doctor";
        }
        if(isset($headers['Type']))
        {
            $keyword = $headers['Type']  ? $headers['Type'] : "Doctor";
        }

        // Debug: Log keyword
        error_log("Auth keyword: " . $keyword);

        /**Step 3 - Is authorization passed with HTTP request ? */
        // Phương pháp 1: Kiểm tra trực tiếp các key cụ thể (cho web)
        if (isset($headers['authorization'])) {
            $Authorization = $headers['authorization'];
        } else if (isset($headers['Authorization'])) {
            $Authorization = $headers['Authorization'];
        } else {
            // Phương pháp 2: Lặp qua tất cả các header (cho mobile)
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'authorization') {
                    $Authorization = $value;
                    break;
                }
            }
        }

        // Kiểm tra REDIRECT_HTTP_AUTHORIZATION từ $_SERVER (cho mobile với Apache)
        if (!$Authorization && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $Authorization = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        // Kiểm tra HTTP_AUTHORIZATION từ $_SERVER (cho mobile với một số server)
        if (!$Authorization && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $Authorization = $_SERVER['HTTP_AUTHORIZATION'];
        }

        // Kiểm tra HTTP_ACCESS_TOKEN từ $_SERVER (được thiết lập bởi .htaccess)
        if (!$Authorization && isset($_SERVER['HTTP_ACCESS_TOKEN'])) {
            $Authorization = 'JWT ' . $_SERVER['HTTP_ACCESS_TOKEN'];
        }

        // Debug: Log Authorization header
        error_log("Authorization header: " . ($Authorization ? $Authorization : "Not set"));
        /**Step 4a - verify token */
        if(isset($Authorization))
        {
            // Xử lý token từ Authorization header
            $accessToken = $Authorization;

            // Kiểm tra và xử lý token với tiền tố "JWT "
            if (strpos($Authorization, 'JWT ') === 0) {
                $accessToken = substr($Authorization, 4); // Bỏ "JWT " ở đầu
            }

            // Debug: Log token sau khi xử lý
            error_log("Token after processing: " . $accessToken);

            try {
                // Decode token
                $decoded = Firebase\JWT\JWT::decode($accessToken, new Firebase\JWT\Key(EC_SALT, 'HS256'));

                // Debug: Log decoded token
                error_log("Decoded token: " . print_r($decoded, true));

                // Kiểm tra xem token có chứa ID không
                if (!isset($decoded->id)) {
                    error_log("Token does not contain user ID");
                    return $AuthUser;
                }

                // Lấy thông tin người dùng
                $AuthenticatedUser = Controller::Model($keyword, $decoded->id);

                // Debug: Log user info
                error_log("User found: " . ($AuthenticatedUser->isAvailable() ? "Yes" : "No"));
                if ($AuthenticatedUser->isAvailable()) {
                    error_log("User active: " . $AuthenticatedUser->get("active"));
                    error_log("User role: " . $AuthenticatedUser->get("role"));
                }

                // Kiểm tra trạng thái active của bác sĩ
                if ($keyword == "Doctor" && $AuthenticatedUser->get("active") != 1) {
                    error_log("Doctor not active");
                    return null;
                }

                // Kiểm tra hash password
                if ($AuthenticatedUser->isAvailable()) {
                    // Nếu token không có hashPass, vẫn xác thực người dùng
                    if (!isset($decoded->hashPass)) {
                        error_log("hashPass not in token, but authenticating user anyway");
                        $AuthUser = $AuthenticatedUser;
                        error_log("Authentication successful without hash verification");
                    }
                    // Nếu có hashPass, kiểm tra khớp với password trong DB
                    else if (md5($AuthenticatedUser->get("password")) == $decoded->hashPass) {
                        $AuthUser = $AuthenticatedUser;
                        error_log("Authentication successful with hash verification");
                    } else {
                        error_log("Hash verification failed");
                        error_log("Expected hash: " . md5($AuthenticatedUser->get("password")));
                        error_log("Token hash: " . $decoded->hashPass);
                    }
                }
            } catch (\Exception $th) {
                // Log lỗi để debug
                error_log("JWT decode error: " . $th->getMessage());
                error_log("Exception trace: " . $th->getTraceAsString());
                return $AuthUser;
            }
        }

        /**Step 4b - if authorization does not set, try cookie or HTTP_ACCESS_TOKEN */
        $accessToken = null;

        // Thử lấy từ cookie
        if (Input::cookie("accessToken")) {
            $accessToken = Input::cookie("accessToken");
            error_log("Found token in cookie: " . substr($accessToken, 0, 10) . "...");
        }
        // Thử lấy từ biến môi trường HTTP_ACCESS_TOKEN (được thiết lập bởi .htaccess)
        else if (isset($_SERVER['HTTP_ACCESS_TOKEN'])) {
            $accessToken = $_SERVER['HTTP_ACCESS_TOKEN'];
            error_log("Found token in HTTP_ACCESS_TOKEN: " . substr($accessToken, 0, 10) . "...");
        }

        // Nếu có accessToken và chưa xác thực được user
        if (!$AuthUser && $accessToken) {
            // Kiểm tra và xử lý token với tiền tố "JWT "
            if (strpos($accessToken, 'JWT ') === 0) {
                $accessToken = substr($accessToken, 4); // Bỏ "JWT " ở đầu
            }

            // Debug: Log cookie token
            error_log("Using token for auth: " . substr($accessToken, 0, 10) . "...");

            try {
                $decoded = Firebase\JWT\JWT::decode($accessToken, new Firebase\JWT\Key(EC_SALT, 'HS256'));

                // Debug: Log decoded cookie token
                error_log("Decoded cookie token: " . print_r($decoded, true));

                // Kiểm tra xem token có chứa ID không
                if (!isset($decoded->id)) {
                    error_log("Cookie token does not contain user ID");
                    return $AuthUser;
                }

                $AuthenticatedUser = Controller::Model($keyword, $decoded->id);

                // Debug: Log user info from cookie
                error_log("User from cookie found: " . ($AuthenticatedUser->isAvailable() ? "Yes" : "No"));
                if ($AuthenticatedUser->isAvailable()) {
                    error_log("User from cookie active: " . $AuthenticatedUser->get("active"));
                    error_log("User from cookie role: " . $AuthenticatedUser->get("role"));
                }

                if ($keyword == "Doctor" && $AuthenticatedUser->get("active") != 1) {
                    error_log("Doctor from cookie not active");
                    return null;
                }

                // Kiểm tra hash password
                if ($AuthenticatedUser->isAvailable()) {
                    // Nếu token không có hashPass, vẫn xác thực người dùng
                    if (!isset($decoded->hashPass)) {
                        error_log("hashPass not in cookie token, but authenticating user anyway");
                        $AuthUser = $AuthenticatedUser;
                        error_log("Authentication from cookie successful without hash verification");
                    }
                    // Nếu có hashPass, kiểm tra khớp với password trong DB
                    else if (md5($AuthenticatedUser->get("password")) == $decoded->hashPass) {
                        $AuthUser = $AuthenticatedUser;
                        error_log("Authentication from cookie successful with hash verification");
                    } else {
                        error_log("Cookie hash verification failed");
                        error_log("Expected hash: " . md5($AuthenticatedUser->get("password")));
                        error_log("Cookie token hash: " . $decoded->hashPass);
                    }
                }
            } catch (\Exception $th) {
                // Log lỗi để debug
                error_log("Cookie JWT decode error: " . $th->getMessage());
                error_log("Cookie exception trace: " . $th->getTraceAsString());
                return $AuthUser;
            }
        }
        return $AuthUser;
    }

    /**
     * Load active and valid plugins
     * And save plugin models in $GLOBALS["_PLUGINS_"];
     *
     * @return self
     */
    private function loadPlugins()
    {
        $Plugins = Controller::model("Plugins");
        $Plugins->where("is_active", "=", 1)->fetchData();

        $GLOBALS["_PLUGINS_"] = [];

        foreach ($Plugins->getDataAs("Plugin") as $p) {
            $idname = $p->get("idname");
            $config_path = PLUGINS_PATH . "/" . $idname . "/config.php";
            if (!file_exists($config_path)) {
                continue;
            }

            $config = include $config_path;
            if (!isset($config["idname"]) || $config["idname"] != $idname) {
                continue;
            }

            $file = PLUGINS_PATH. "/" . $idname . "/" . $idname . ".php";
            if (file_exists($file)) {
                require_once $file;
            }

            $GLOBALS["_PLUGINS_"][$config["idname"]] = [
                "config" => $config,
                "model" => $p
            ];
            Event::trigger("plugin.load", $p);
        }

        $this->loadInt();
    }


    /**
     * Load active theme (skin)
     * @return void
     */
    private function loadTheme()
    {
        $idname = active_theme("idname");
        $config_file = active_theme("path") . "/config.php";
        $loader_file = active_theme("path") . "/" . $idname . ".php";

        if (!file_exists($config_file) || !file_exists($loader_file)) {
            return;
        }

        // Load and check config file
        $config = include $config_file;
        if (!isset($config["idname"]) || $config["idname"] != $idname) {
            return;
        }

        // Load the them
        require_once $loader_file;

        // trigger theme load event
        Event::trigger("theme.load");
    }


    private function loadInt()
    {
        $l = null;
        $f = APPPATH."/inc/license";
        if (file_exists($f) && is_readable($f)) {
            $l = @file_get_contents($f);
        }

        $t = 0;
        if ($l && count(explode("&", $l)) > 1) {
            $x = explode("&", $l, 2);
            $l = $x[0];
            $t = (int)$x[1];
        }

        if ($t + 2592000 > time()) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://api.getnextpost.io/l/");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            "u" => APPURL,
            "l" => $l,
            "v" => APP_VERSION]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $r = curl_exec ($ch);
        curl_close ($ch);

        if (is_writable(APPPATH."/inc/")) {
            @file_put_contents($f, $l."&".time());
        }
    }



    /**
     * Define ACTIVE_LANG constant
     * Include languge strings
     */
    private function i18n()
    {
        $Route = $this->controller->getVariable("Route");
        $AuthUser = $this->controller->getVariable("AuthUser");
        $IpInfo = $this->controller->getVariable("IpInfo");

        if ($AuthUser) {
            // Get saved lang code for authorized user.
            $lang = $AuthUser->get("preferences.language");
        } else if (isset($Route->params->lang)) {
            // Direct link or language change
            // Getting lang from route
            $lang = $Route->params->lang;
        } else if (Input::cookie("lang")) {
            // Returninn user (non-auth),
            // Getting lang. from the cookie
            $lang = Input::cookie("lang");
        } else {
            // New user
            // Getting lang. from ip-info
            $lang = Config::get("default_applang");

            if ($IpInfo->languages) {
                foreach ($IpInfo->languages as $l) {
                    foreach (Config::get("applangs") as $al) {
                        if ($al["code"] == $l || $al["shortcode"] == $l) {
                            // found, break loops
                            $lang = $al["code"];
                            break 2;
                        }
                    }
                }
            }
        }


        // Validate found language code
        $active_lang = Config::get("default_applang");
        foreach (Config::get("applangs") as $al) {
            if ($al["code"] == $lang || $al["shortcode"] == $lang) {
                // found, break loop
                $active_lang = $al["code"];
                break;
            }
        }

        define("ACTIVE_LANG", $active_lang);
        @setcookie("lang", ACTIVE_LANG, time()+30 * 86400, "/");


        $Translator = new Gettext\Translator;

        // Load app. locale
        $path = APPPATH . "/locale/" . ACTIVE_LANG . "/messages.po";
        if (file_exists($path)) {
            $translations = Gettext\Translations::fromPoFile($path);
            $Translator->loadTranslations($translations);
        }

        // Load theme locale
        $path = active_theme("path") . "/locale/" . ACTIVE_LANG . "/messages.po";
        if (file_exists($path)) {
            $translations = Gettext\Translations::fromPoFile($path);
            $Translator->loadTranslations($translations);
        }

        // Load plugins locales
        // foreach ($GLOBALS["_PLUGINS_"] as $idname => $p) {
        //     $path = PLUGINS_PATH . "/" .$idname . "/locale/" . ACTIVE_LANG . "/messages.po";
        //     if (file_exists($path)) {
        //         $translations = Gettext\Translations::fromPoFile($path);
        //         $Translator->loadTranslations($translations);
        //     }
        // }

        $Translator->register(); // Register global functions

        // Set other library locales
        try {
            \Moment\Moment::setLocale(str_replace("-", "_", ACTIVE_LANG));
        } catch (Exception $e) {
            // Couldn't load locale
            // There is nothing to do here,
            // Fallback to default language
        }
    }


    /**
     * Analize route and load proper controller
     * @return App
     */
    private function route()
    {
        // Initialize the router
        $router = new AltoRouter();
        $router->setBasePath(BASEPATH);

        // Load plugin/theme routes first
        // TODO: Update router.map in modules to App::addRoute();
        $GLOBALS["_ROUTER_"] = $router;
        \Event::trigger("router.map", "_ROUTER_");
        $router = $GLOBALS["_ROUTER_"];

        // Load internal routes
        $this->addInternalRoutes();

        // Load global routes
        include APPPATH."/inc/routes.inc.php";

        // Map the routes
        $router->addRoutes(App::getRoutes());

        // Match the route
        $route = $router->match();
        $route = json_decode(json_encode($route));

        if ($route) {
            if (is_array($route->target)) {
                require_once $route->target[0];
                $controller = $route->target[1];
            } else {
                $controller = $route->target."Controller";
            }
        } else {
            header("HTTP/1.0 404 Not Found");
            $controller = "IndexController";
        }

        $this->controller = new $controller;
        $this->controller->setVariable("Route", $route);
    }


    /**
     * Map the routes which are for
     * internal use only.
     */
    private function addInternalRoutes()
    {
        // Media Grabber
        App::addRoute("POST", "/mg/?", "MediaGrabber");

        // Webhooks for payment gateways
        App::addRoute("GET|POST", "/webhooks/payments/[a:gateway]/?", "PaymentWebhook");

        // File Manager (Connector for inline)
        App::addRoute("GET|POST", "/file-manager/connector/?", "FileManager");
    }




    /**
     * Process
     */
    public function process()
    {
        // Khởi tạo session nếu chưa được khởi tạo
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Define global variables
        $GLOBALS["PaymentGateways"] = [];


        /**
         * Create database connection
         */
        $this->db();

        /**
         * Get IP Info
         */
        $IpInfo = $this->ipinfo();

        /**
         * Auth.
         */
        $AuthUser = $this->auth();

        /**
         * Load plugins
         */
        // $this->loadPlugins();

        /**
         * Load active theme
         */
        $this->loadTheme();


        /**
         * Analize the route
         */
        $this->route();
        $this->controller->setVariable("IpInfo", $IpInfo);
        $this->controller->setVariable("AuthUser", $AuthUser);

        /**
         * Init. locales
         */
        $this->i18n();


        $this->controller->process();
    }
}