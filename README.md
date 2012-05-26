# Core Updates

These core updates represent a shift in the expectation of CodeIgniter. They implement some convention, instead of CI-style configuration. They're not for everyone but definitely for me.

## Controller

### Method Names

Method names have been updated to include a METHOD prefix. Typically, that means you'd be prefacing methods with `get_`, however `post_`, `put_`, and any other valid request methods are valid.

```php
<?php
class Posts extends MY_Controller {
    public function get_index() {
        // ...
    }
}
```

### Filters

Two filters are added to all controllers, `before` and `after`. They may be defined as a single method, an array of methods, or a protected method.

```php
<?php
class Posts extends MY_Controller {
    protected $before_filter = 'log_views';
    protected $before_filter = array('log_views', 'check_auth');
    protected function before_filter() {

    }
    protected $after_filter = 'clear_sessions';
    protected $after_filter = array('clear_sessions', 'cleanup_tmp');
    protected function after_filter() {

    }
}
```

### View Loading

View loading is now automated and therefore called after every controller method. The name of the view is inferred from the class name and the method name. So, `Posts::get_index()` would load a view: `posts/index` and `Users::post_confirm()` would load `users/confirm`. You can override this by setting the `view` class variable.

```php
<?php
class Posts extends MY_Controller {
    public function get_index() {
        // ... loads view: posts/index.php
    }
    public function get_index() {
        $this->view = 'blog/index';
        // ... loads view: blog/index.php
    }
}
```

To pass data to a view simply set the variable as a member of the controller class. Any class variables set after `__construct` runs will be passed to the view.

```php
<?php
class Posts extends MY_Controller {
    public function __construct() {
        $this->lib = // ... not passed to view
    }
    protected function before_filter() {
        $this->sidebar = // ... passed to view
    }
    public function get_index() {
        $this->posts = // ... passed to view
    }
}
```

To skip automatic view loading return any non-false value from your controller. That value will then be rendered to the page.

### Layouts

By default, all views are loaded within a global application layout. The layout is defined by the protected class variable `$layout` and defaults to `application/layout`.

The layout is rendered with the contents of the sub-view contained within the `$yield` variable. In addition, the layout has access to any class variables defined within the controller method or the sub-view.

```php
<?php
// controllers/posts.php
class Posts extends MY_Controller {
    public function get_index() {
        $this->posts = // ... passed to view and layout
    }
}
```

```html
<!-- views/posts/index.php -->
<?php $this->title = 'test'; ?>
<div> ... </div>
```

```html
<!-- views/application/layout.php -->
<html>
<head>
    <title><?=$title?></title>
</head>
<body>
    <?=$yield?>
</body>
</html>
```

### Headers

A few common headers are automatically applied to all requests. They are:

* `Content-type: text/html`
* `Cache-control: no-cache` (also sets `Edge-control: no-cache`)

To override the defaults set the class variables within your controller or within your methods.

```php
<?php
class Posts extends MY_Controller {
    protected $content_type = 'application/xml';
    public function get_index() {
        $this->content_type = 'text/json';
        $this->cache_control = 'max-age=300, public'
    }
}
```

Of note: If you set the `content_type` to `text/json` errors and data returned
from your controller will be automatically wrapped in `json_encode()`.

### Errors

Any exceptions thrown and not caught during execution of the controller method, the before, or the after filters will be presented within the CI `show_error` function. The method will be passed the `getMessage()` value of the exception.