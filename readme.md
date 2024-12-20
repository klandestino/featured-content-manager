# Featured Content Manager

This plugin lets you create sortable and nested lists (Featured Content Areas) of content you want to feature on your website. These sortable lists can contain content of ```object_type``` ```post``` and ```term```.

## Build

1. Run `$ npm install` to install dependencies.
2. Run `$ npm build` to build assets as javascript, styles and images.

## Installation

To start using Featured Content Manager you nead to add support for it in your WordPress theme like the example below.

```php
add_theme_support( 'featured-content-manager',
	array(
		'featured_areas' => [
			'posts'    => [
				'title'           => 'Featured Posts Area',
				'max'             => 10, // Default is 10.
				'levels'          => 2,  // Default is 1.
				'object_type'     => 'post',
				'object_subtypes' => [ 'post' ],
			],
			'category' => [
				'title'           => 'Featured Categories Area',
				'max'             => 5,
				'object_type'     => 'term',
				'object_subtypes' => [ 'category', 'post_tag' ],
			]
		],
	)
);
```

## Todo:

This is a short list of features that may be included in the future.

* x Add level of nestable in theme_support.
* x Add max number of items in list in theme_support
* ☐ Add post_status as a setting in theme_support
