# Featured Content Manager

Detta plugin skapar en posttyp med tillhörande taxonomier för att kunna styra hur innehåll skall sorteras och visas i ett visst flöde. Genom functionen add_theme_support() kan du i ditt tema bestämma vilka flöden som skall finnas samt vilken postdata som man skall kunna ändra för flödets innehåll.

## Installation och användning

För att komma igång med Featured Content Manager behöver du lägga till stöd för det i ditt tema enligt exemplet nedan:

```php
add_theme_support( 'featured-content-manager',
	array(
		'fields' => [
			'post_title', // Namnet på en variabel i post-objectet
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

* ✓ Skapa rest end point som skapar featured item utifrån customizerns datamodel (och duplicerar posten)
* ✓ Ser till att customizern skiljer på Create item och Add item (skapar featured item, lägger till den grafiskt)
* ✓ Skapar Featured items vid sparning utifrån det som finns i customizerns setting
* ☐ Skapa funktion för att ge tillbaka WP Query
* ☐ Fixa thumbnail media
* ☐ Lägg till tag/category (select) som variabel
* ☐ RENSA I JS
* ☐ Refacturing av FCM (publika funktioner/privata/vilken klass/delade funktioner osv)