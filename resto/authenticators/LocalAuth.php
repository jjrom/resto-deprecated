<?php
include ('AbstractAuth.php');
include ('AuthDatabaseConnector.php');
class SSOAuth extends AbstractAuth {
    
    private $profile;
    private $user_mail;

    /**
     * Get auth filter for http method
     *
     * @param {String} collection : name of the collection 
     *        {String} action : action on which the filter is (ex : download, visualize, search)
     *
     * @return {Json} query
     */
    public function getAuthFilter_DELETE($collection, $action){
        
    }
    
    /**
     * Get auth filter for http method GET
     *
     * @param {String} collection : name of the collection 
     *        {String} action : action on which the filter is (ex : download, visualize, search)
     *
     * @return {Json} query
     */
    public function getAuthFilter_GET($collection, $action){
        if ($this->checkAuth()){
            return $this->getUserFilter($user_mail, $collection, "GET", $action);
        }else{
            return NULL;
        }
    }
    
    public function getAuthFilter_POST($collection, $action){
        
    }
    public function getAuthFilter_PUT($collection, $action){
        
    }
    
    /*
     * Get the user profile
     *      guest or registered 
     *
     * @return {String} profile
     */
    public function getProfile() {
            return $profile;
    }
    
    /**
     * Check if the user is authenticated
     *
     * @return {boolean}
     */
    public function checkAuth() {
        // TODO : 
        //      1. récupérer les infos utilisateurs depuis le SSO serveur
        //          a. Pas authentifié : return FALSE
        //          b. Authentifié : étape 2
        //      2. extraire les infos permettant de retrouver l'utilisateur dans la bdd user de l'application
        //      3. vérifier que l'utilisateur est enregistré sur l'application
        //          a. OUI -> lui donner le profil registered et return TRUE
        //          b. NON -> lui donner le profil guest et return FALSE
        
        //  1. 
        $sso_user_info = $this->getSSOAuth();
        if ($sso_user_info === NULL){
            return false;
        }else{
            // 2.
            $sso_user_info = json_decode($sso_user_info, true);
            $sso_email = $sso_user_info[SSO_EMAIL];
            
            // 3.
            if (!empty($sso_email)){
                $authDatabaseConnector = new AuthDatabaseConnector();
                $db = $authDatabaseConnector->getConnection();
                
                // SELECT email FROM user WHERE email=sso_email;
                $query = "SELECT email FROM users WHERE email='".$sso_email."'";
                $result = pg_query($db, $query);
                if ($result === false){
                    echo "resutl null... aie";
                    return false;
                }
                while ($row = pg_fetch_row($result)) {
                    $email = $row[0];
                }
                
                //$selectfields = array("email" => "");
                //$email = pg_select($db,"users",$selectfields);
                
                if ($email === $sso_email){
                    $this->profile = REGISTERED;
                    $this->user_mail = $email; 
                    return true;
                }else{
                    $this->profile = GUEST;
                    return false;
                }
                
            }else{
                $this->profile = GUEST;
                return false;
            }
        }
    }


    /*
     * TODO : move define in param file
     *
     */
    public function __construct(){
        define("IDENTITY_SERVER_ROOT_URL", "https://sso.kalimsat.eu/");
        define("ACCESS_TOKEN", "access_token");
        define("CODE", "code");
        define("GRANT_TYPE", "grant_type");
        define("AUTHORIZATION_CODE", "authorization_code");
        define("REFRESH_TOKEN", "refresh_token");
        define("REDIRECT_URI", "redirect_uri");
        define("SCOPE", "scope");
        define("STATE", "state");
        define("OPENID", "openid");

        define("CLIENT_REDIRECT_URI", "https://localhost/resto/auth/callback.php");
        define("CLIENT_BASE64_CODE", "TktJT0RIV2xrcEpueEtqNHNjSE5vREtJSDNjYTpEaEtJZ2FmR0JmT3FLT2ZnUnFnaVZMckRfa3Nh");
        define("SSO_SERVER_URL", "sso.kalimsat.eu");
        define("SSO_SERVER_URL_GET_INFOS", "https://sso.kalimsat.eu/oauth2/userinfo?schema=openid");

        define("KEY_USER_INFO", "user_info");
        define("KEY_TOKEN", "token");
        
        define("SSO_EMAIL", "http://theia.org/claims/emailaddress");
        
        define("GUEST", "guest");
        define("REGISTERED", "registered");
    }

    /**
     * Object to store default DB configuration
     */
    public $config = array(
        'dbname' => 'resto',
        'host' => 'localhost',
        'port' => '5432',
        'table' => 'users', // Default name of collection table within a schema
        'user' => 'resto',
        'suser' => 'sresto',
        'resultsPerPage' => 50, // Number of results returned by page
        'maximumResultsPerPage' => 500 // Maximum number of results returned by page
    );

    /**
     * Get SSO authentification.
     * Return user informations if the user is logged on the SSO server
     *
     * @return {Json} user infos
     *
     */
    private function getSSOAuth() {
        if (isset($_SESSION['token'])){   
            $ch = curl_init(SSO_SERVER_URL_GET_INFOS);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $_SESSION['token']) );
            $authInfo = curl_exec($ch);
            curl_close($ch);
            return $authInfo;  
        }
        return NULL;
    }
    
    
    private function getUserFilter($email, $collection, $method, $action){
        $authDatabaseConnector = new AuthDatabaseConnector();
        $db = $authDatabaseConnector->getConnection();
                
        // 1. obtenir l'id correspondant au mail
        // 2. récupérer la liste des filtres associé à cet id
        // 3. selectionner le filtre qui correspond à la collection
        
        // SELECT email FROM user WHERE email=sso_email;
        $selectfields = array("email" => "");
        $email = pg_select($db,"user",$selectfields);
    }
    
    public function closeSession(){
        
    }
    
}
?>