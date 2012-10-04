# Wu

A "less-is-more" templating engine. It features minimalist syntax, extensibility via hooks, JIT compilation and a modest footprint (< 200 lines of code).



## Design goals

- terse and predictable syntax
- extensibility and expressiveness
- reasonable performance



## Syntax

Here's a template example:

```html
<section :chat:>
	<blockquote>:content:</blockquote>
	<small>:author:</small>
</section>
```

The data to populate the template above might look like this:

```php
<?php
$greeting = [
	'author' => 'Teddy',
	'content' => 'Hello world'
];

$data['chat'] = $greeting;

echo Template::render('template.html', $data);
?>
```

The output would look like this:

```html
<section>
	<blockquote>Hello world</blockquote>
	<small>Teddy</small>
</section>
```

You can display several chat messages without changing the template:

```php
<?php
$data['chat'] = [
	['author' => 'Teddy', 'content' => 'Hello world'],
	['author' => 'World', 'content' => 'Hi Teddy']
];

echo Template::render('template.html', $data);
?>
```
	
The template automatically takes care of looping through them:

```html	
<section>
	<blockquote>Hello world</blockquote>
	<small>Teddy</small>
	
	<blockquote>Hi Teddy</blockquote>
	<small>World</small>
</section>
```

### Includes

Templates support static inclusion of other templates. To illustrate, we could split the previous template into two files like this:

```html
<section :chat:>:chat.html:</section>

<!--chat.html-->
<blockquote>:content:</blockquote>
<small>:author:</small>
```

## Hooks

Template functionality can be extended via hooks. There are two hooks out of the box: `:else:` and `:raw:`

### `:else:` Hook

This hook works like the else statement in Python:

```html
<section :chat:>:chat.html:</section>
<div :else:>Be the first to say something</div>
```

### `:raw:` Hook

Templates escape HTML in values by default. This hook allows a developer to unescape it and print arbitrary markup.

If we wanted to allow HTML content in a chat message, we could do this:

```html
<blockquote :raw:>:content:</blockquote>
<small>:author:</small>
```



## A note on data types and implementation details

Although the syntax looks very minimalist, there are some rules governing where certain variable types can occur. Templates do three types of variable binding: 

- collection binding: to bind an array or object to a template variable, the variable must appear as an attribute name on an HTML element, e.g. `<div :collection:></div>`.
  
  Binding anything that is not either an array or an object as an HTML attribute will cause the variable binding to be ignored. Binding collections anywhere else yields undefined behavior.
  
  Note that variable names for collection bindings must conform to [XML attribute name rules](http://razzed.com/2009/01/30/valid-characters-in-attribute-names-in-htmlxml/) (e.g., `:book-list:` is valid, `:books+magazines:` is not).
  
- scalar binding: aka printable values (strings, numbers, included templates, etc) can appear as attribute values and within DOM text nodes.

- hook binding: much like collection bindings, hooks must appear as attribute names on HTML elements, e.g. `<div :else:></div>`.



## Creating custom hooks

Any defined class whose name ends in `Hook` is considered a hook. For example, the `:else:` hook is implemented in the `ElseHook` class.

There are two ways to implement hooks:

- `format` hooks can be used to format values prior to printing them
- `macro` hooks can modify the template's DOM at compile time.

### `format` Hooks

These hooks must have a static method called `format`, which takes a printable value as a parameter and returns a printable value.

The `:raw:` hook is an example of `format` hooks:

```php
<?php
class RawHook {
	static function format($value) {
		return htmlspecialchars_decode($value, ENT_QUOTES | ENT_HTML5);
	}
}
?>
```

### `macro` Hooks

These hooks must have a static method called `macro`, which takes a DOMNode as a parameter.

```php
<?php
class ElseHook {
	static function macro(DOMNode $el) {
		/*...*/
	}
}
?>
```

`macro` hooks are meant to allow you to insert arbitrary PHP code at arbitrary points in the DOM. PHP tags can be inserted by creating DOMCdataSection nodes.

Note that modifying elements up on the DOM tree from the parameter element yields undefined behavior (and it breaks macro users' expectations, so just don't do it).

To see an example of a `macro` hook, see [elsehook.php](https://github.com/lhorie/wu-template/blob/master/elsehook.php)

### A note on hook development

Note that for `Template::render()` calls, templates are only recompiled if they have been modified. While developing hooks, you can manually force compilation of test templates:

```php
<?php
Template::save($compiledfilename, Template::compile(file_get_contents($filename)));
?>
```



## Miscellaneous notes

- requires PHP 5.4
- there are no plans to extend template syntax. The class API may change to improve support for hook development
- there are no plans to add in-template inheritance, since it generally leads to duplication of `extends` directives (which need to be ignored when reusing templates anyways)

