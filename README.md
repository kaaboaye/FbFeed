### FbFeed

# USAGE:
- FbFeed class:
    class FbFeed ( array $CONF = [
        'pageID' => var 'your page ID', #required
        'appID' => var 'your Facebook App ID', #required
        'appSecret' => var 'your Facebook App secret', #required
        'token' => var 'your app token', #optional
        'debug' => bool,
            - optional,
            - WARNING:
              - debug is very precise
              - and it will leak for example App secret
              - so it HAVE to be DISABLE (FALSE) on production server,
            - default = false
        'APIurl' => var 'url to Facebook API',
            - optional,
            - default = 'https://graph.facebook.com/v2.7'
        'feedLimit' => int 'number of posts to download'
            - optional,
            - maximum = 100,
            - default = 25
        'date' => string 'date format output'
            - optional,
            - default = "d-m-Y G-i-s",
            - manual = 'http://php.net/manual/en/function.date.php#refsect1-function.date-parameters'
        'template' => var 'template for feed',
            - optiobal,
            - default = '
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

# EXAMPLE:
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

# VERSION:
- 2.0

# LICENCE:
- If you are using this script:
    - you are agreeing with the licence,
    - you have to credit ORGINAL_AUTHOR in your project.
- You can do everything except:
    - remove and edit (ORGINAL_AUTHOR and LICENCE) section in this header.

# ORGINAL_AUTHOR:
- name: Mieszko Wawrzyniak
- nickname: kaaboaye
- e-mail: kaaboaye(at)gmail(dot)com
