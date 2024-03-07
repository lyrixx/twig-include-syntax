# Twig Include Syntax

Bored to update twig include syntax by hand? Me too!

You don't understand what I'm talking about? Let me explain.

When you include a file in twig, you have to use the following syntax:

```twig
{{ include('path/to/file.html.twig') }}
```

It's semantically more correct, than the following syntax:

```twig
{% include 'path/to/file.html.twig' %}
```

So I made this to apply this kind of changes:

```diff
 {% block header %}
-     {% include "_header.html.twig" %}
+     {{ include('_header.html.twig') }}
 {% endblock %}
```

## Installation

Go to the release page and download the latest version.

You don't even need PHP installed on your machine, just download the static
binary file and you are ready to go.

## Warning

* This does't hand whitespace control
* It works on my projects, but it may not work on yours
* There is no tests and the regex is quite complex
