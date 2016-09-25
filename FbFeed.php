<?php
/*
USAGE:
- FbFeed class:
    class FbFeed ( array $CONF = [
        'pageID' => var 'your page ID', #required
        'appID' => var 'your Facebook App ID', #required
        'appSecret' => var 'your Facebook App secret', #required
        'token' => var 'your app token', #optional
        'debug' => bool,
            # - optional,
            # - WARNING:
              - debug is very precise
              - and it will leak for example App secret
              - so it HAVE to be DISABLE (FALSE) on production server,
            # - default = false
        'APIurl' => var 'url to Facebook API',
            # - optional,
            # - default = 'https://graph.facebook.com/v2.7'
        'feedLimit' => int 'number of posts to download'
            # - optional,
            # - maximum = 100,
            # - default = 25
        'date' => string 'date format output'
            # - optional,
            # - default = "d-m-Y G-i-s",
            # - manual = 'http://php.net/manual/en/function.date.php#refsect1-function.date-parameters'
        'template' => var 'template for feed',
            # - optiobal,
            # - default = '
                <div class="post">
                    <a href="%url%">
                        %name%
                        %date%
                        %content%
                    </a>
                </div>
                ';
        ]);

- class methods:
    - render_feed() rendering bootstrap's style page feed,
      *tip: you can customize it using $CONF['template'] variable
    - for more details just use CTRL+F and type 'public function'

- class variables:
    - you do not have access to variables, use thees methods:
        - FbFeed::config() : array $CONF # object config
        - FbFeed::token() : string $token # Facebook Graph API access token
        - FbFeed::data() : array $data # data downloaded from Facebook Graph API
    - USAGE:
        - $Foo->data() # to get data
        - $Foo->data($data) # to set data

- template variables:
    - %url% : string/url # permalink to post
    - %date% : string/txt # last update date
    - %name% : string/txt # post title
    - %icon% : string/url # post icon
    - %message% : string/txt # post content _only text
    - %content% : string/html # post content _full post

EXAMPLE:
<?php
    $Foo = new FbFeed([
        'pageID' => '3453456345',
        'appID' => '34534563453453456345',
        'appSecret' => '345345634534534563453453456345',
        'feedLimit' => 10,
        'template' => $template,
    ]);

    echo $Foo->render_feed();
?>

VERSION:
- 2.0

LICENCE:
- If you are using this script:
    - you are agreeing with the licence,
    - you have to credit ORGINAL_AUTHOR in your project.
- You can do everything except:
    - remove and edit (ORGINAL_AUTHOR and LICENCE) section in this header.

ORGINAL_AUTHOR:
- name: Mieszko Wawrzyniak
- nickname: kaaboaye
- e-mail: kaaboaye(at)gmail(dot)com
*/

class FbFeed {
    private $CONF;
    private $isCONFok = false;
    private $token;
    private $data;

    function __construct(array $CONF = []){
        if($CONF != []){
            $this->CONF = $CONF;
            $this->check_config();
        }
    }

    private function check_config(){
        if(!(
            isset($this->CONF['appID']) &&
            isset($this->CONF['appSecret']) &&
            isset($this->CONF['pageID'])
        )){
            echo 'Error: Facebook API parametrs are not definied';
            exit;
        }

        if(!(
            isset($this->CONF['feedLimit']) &&
            is_int($this->CONF['feedLimit']) &&
            $this->CONF['feedLimit'] <= 100
        )){
            $this->CONF['feedLimit'] = 25;
        }

        if(!(
            isset($this->CONF['date']) &&
            is_string($this->CONF['date'])
        )){
            $this->CONF['date'] = "d-m-Y G-i-s";
        }

        if(!(
            isset($this->CONF['template'])
            )){
            $this->CONF['template'] = '
                <div class="post">
                    <a href="%url%">
                        %name%
                        %date%
                        %content%
                    </a>
                </div>
                ';
        }

        if(!(
            isset($this->CONF['debug']) &&
            is_bool($this->CONF['debug'])
            )){
            $this->CONF['debug'] = false;
        }

        if(!(
            isset($this->CONF['APIurl'])
            )){
            $this->CONF['APIurl'] = 'https://graph.facebook.com/v2.7';
        }
    }

    private function request($request, $convertJSON = true){
        $url = $this->CONF['APIurl'] . $request;

        if($this->CONF['debug']) {
            echo 'API request: ' . $url . "<br>\n";
        }

        $result = file_get_contents($url);

        if($convertJSON){
            $result = json_decode($result, true);
        }

        return $result;
    }

    private function get_token(){
        $token = $this->request(
            '/oauth/access_token?' .
            'client_id=' . $this->CONF['appID'] .
            '&client_secret=' . $this->CONF['appSecret'] .
            '&grant_type=client_credentials'
            );

        if(!$this->echo_error($token)){
            $this->token = $token['access_token'];
        }

    }

    private function call_api(){
        $data = $this->request(
            '/' . $this->CONF['pageID'] .
            '?access_token=' . $this->token() .
            '&fields=name,cover,feed.limit(' . $this->CONF['feedLimit'] . '){full_picture,picture,message,name,type,permalink_url,updated_time,source}'
            );

        if(!$this->echo_error($data)){
            $this->data = $data;
        }
    }

    public function render_feed(){
        $output = '';

        foreach ($this->data()['feed']['data'] as $post) {
            $element = $this->CONF['template'];

            $element = str_replace('%url%', $post['permalink_url'], $element);
            $element = str_replace('%date%', $this->post_date($post['updated_time']), $element);

            if(isset($post['name'])){ # Post title
                $element = str_replace('%name%', $post['name'], $element);
            }
            elseif(isset($this->data()['name'])) {
                $element = str_replace('%name%', $this->data()['name'], $element);
            }

            if(isset($post['picture'])){ # Icon
                $element = str_replace('%icon%', $post['picture'], $element);
            }
            elseif(isset($post['full_picture'])) {
                $element = str_replace('%icon%', $post['full_picture'], $element);
            }
            elseif(isset($post['cover']['source'])) {
                $element = str_replace('%icon%', $this->data()['cover']['source'], $element);
            }


            if(isset($post['message'])){ # Message only
                $element = str_replace('%message%', $post['message'], $element);
            }

            $content = ''; # Full post

            if(isset($post['message'])){
                $content .= '<p>' . $post['message'] . '</p>';
            }

            if(isset($post['full_picture']) && !isset($post['source'])){ # Full size picture
                $content .= '<img src="' . $post['full_picture'] . '"></img>';
            }

            if(isset($post['source'])){ # Video
                $content .= '<iframe src="' . $post['source'] . '"></iframe>';
            }

            $element = str_replace('%content%', $content, $element);

            $output .= $element;
        }

        return $output;
    }

    private function post_date($date){
        $date = strtotime($date);
        $date = date($this->CONF['date']);

        return $date;
    }

    public function config($config = null){
        if(empty($config)){
            if(empty($this->config)){
                $this->call_api();
            }

            return $this->config;
        } else {
            $this->config = $config;
        }
    }

    public function token($token = null){
        if(empty($token)){
            if(empty($this->token)){
                $this->get_token();
            }

            return $this->token;
        } else {
            $this->token = $token;
        }
    }

    public function data($data = null){
        if(empty($data)){
            if(empty($this->data)){
                $this->call_api();
            }

            return $this->data;
        } else {
            $this->data = $data;
        }
    }

    private function echo_error($result){
        if($this->CONF['debug']){
            if(isset($result['error'])){
                echo 'Error: Can not get access token';
                echo 'Message: ' . $result['error'];
                return true;
            } else {
                return false;
            }
        } else {
            if(isset($result['error'])){
                echo 'Error. Debug is off';
                return true;
            } else {
                return false;
            }
        }
    }
}
