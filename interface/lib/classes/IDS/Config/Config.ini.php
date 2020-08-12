; <?php die(); ?>

; PHPIDS Config.ini

; General configuration settings

[General]

    ; basic settings - customize to make the PHPIDS work at all
    filter_type     = xml

    base_path       = /full/path/to/IDS/
    use_base_path   = false

    filter_path     = default_filter.xml
    tmp_path        = tmp
    scan_keys       = false

    ; in case you want to use a different HTMLPurifier source, specify it here
    ; By default, those files are used that are being shipped with PHPIDS
    HTML_Purifier_Cache = vendors/htmlpurifier/HTMLPurifier/DefinitionCache/Serializer

    ; define which fields contain html and need preparation before
    ; hitting the PHPIDS rules (new in PHPIDS 0.5)
    ;html[]          = POST.__wysiwyg
	;html[]			 = POST.records
	;html[]			 = REQUEST.records

    ; define which fields contain JSON data and should be treated as such
    ; for fewer false positives (new in PHPIDS 0.5.3)
    ;json[]          = POST.__jsondata

    ; define which fields shouldn't be monitored (a[b]=c should be referenced via a.b)
    ; exceptions[]    = GET.__utmz
    ; exceptions[]    = GET.__utmc
	; exceptions[]    = POST.maildir_path
	; exceptions[]	= REQUEST.maildir_path
	; exceptions[]    = REQUEST.website_path
	; exceptions[]    = REQUEST.website_symlinks
	; exceptions[]    = REQUEST.vhost_conf_dir
	; exceptions[]    = REQUEST.vhost_conf_enabled_dir
	; exceptions[]    = REQUEST.nginx_vhost_conf_dir
	; exceptions[]    = REQUEST.nginx_vhost_conf_enabled_dir
	; exceptions[]    = REQUEST.php_open_basedir
	; exceptions[]    = REQUEST.awstats_pl
	; exceptions[]    = POST.website_path
	; exceptions[]    = POST.website_symlinks
	; exceptions[]    = POST.vhost_conf_dir
	; exceptions[]    = POST.vhost_conf_enabled_dir
	; exceptions[]    = POST.apache_init_script
	; exceptions[]    = POST.nginx_vhost_conf_dir
	; exceptions[]    = POST.nginx_vhost_conf_enabled_dir
	; exceptions[]    = POST.php_open_basedir
	; exceptions[]    = POST.awstats_pl
	; exceptions[]    = REQUEST.fastcgi_starter_path
	; exceptions[]    = REQUEST.fastcgi_bin
	; exceptions[]    = POST.fastcgi_starter_path
	; exceptions[]    = POST.fastcgi_bin
	; exceptions[]    = REQUEST.jailkit_chroot_home
	; exceptions[]    = POST.jailkit_chroot_home
	; exceptions[]    = REQUEST.phpmyadmin_url
	; exceptions[]    = REQUEST.phpmyadmin_url
	; exceptions[]    = REQUEST.records.weak_password_txt
	; exceptions[]    = POST.records.weak_password_txt
	
	

    ; you can use regular expressions for wildcard exceptions - example: /.*foo/i

[Caching]

    ; caching:      session|file|database|memcached|apc|none
    caching         = file
    expiration_time = 600

    ; file cache
    path            = tmp/default_filter.cache

    ; database cache
    wrapper         = "mysql:host=localhost;port=3306;dbname=phpids"
    user            = phpids_user
    password        = 123456
    table           = cache

    ; memcached
    ;host           = localhost
    ;port           = 11211
    ;key_prefix     = PHPIDS

    ; apc
    ;key_prefix     = PHPIDS

