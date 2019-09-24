# Featured Content Manager

Detta plugin skapar en posttyp med tillhörande taxonomier för att kunna styra hur innehåll skall sorteras och visas i ett visst flöde. Genom functionen ```add_theme_support()``` kan du i ditt tema bestämma vilka flöden som skall finnas samt vilken postdata som man skall kunna ändra för flödets innehåll.

## Installation och användning

För att komma igång med Featured Content Manager behöver du lägga till stöd för det i ditt tema enligt exemplet nedan:

```php
add_theme_support( 'featured-content-manager',
	array(
		'fields' => [
			'post_title',
			'post_excerpt',
			'thumbnail',
			'select' => [
				'name'         => 'style',
				'display_name' => 'Utseende',
				'values'       => [
					'standard' => 'Standard',
					'large'    => 'Stor',
				],
			],
		],
		'featured_areas' => [
			'Första',
			'Andra',
		],
	)
);
```

## Att göra:

Dessa saker finns kvar att göra

* ☐ Lägg till nivåer som inställnig i theme_support
* ☐ Lägg till antal tillåtna inlägg som inställnig i theme_support
* ☐ Lägg till inställnig för om future skall tillåtas i theme_support
* ☐ Featured Area bör bestå av nyckel och värde.
* ☐ Gör en white list i REST funktionen som rensar mer post data och baserat på theme_support
* ☐ Lyft över fields till att ligga i enskilda areas
* ☐ Gör ett white list lager i JS där fields läses för varje area