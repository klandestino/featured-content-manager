# Featured Content Manager

Detta plugin skapar en posttyp med tillhörande taxonomier för att kunna styra hur innehåll skall sorteras och visas i ett visst flöde. Genom functionen ```add_theme_support()``` kan du i ditt tema bestämma vilka flöden som skall finnas samt vilken postdata som man skall kunna ändra för flödets innehåll.

## Installation och användning

För att komma igång med Featured Content Manager behöver du lägga till stöd för det i ditt tema enligt exemplet nedan:

```php
add_theme_support( 'featured-content-manager',
	array(
		'fields' => [
			'post_title', // Namnet på en attribut i WP_Post.
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