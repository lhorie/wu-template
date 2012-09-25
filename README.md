# Wu

A "less-is-more" templating engine. It's some 200 lines of code, but it has a JIT compiler and it is extensible via hooks.

## Binding data

    hello.php
    ---------
    <?php
    Template::render('hello.html', ['name' => 'John']);
    ?>
    
    hello.html
    ----------
    <p>Hello :name:</p>

    output
    ------
    <p>Hello John</p>

## Arrays

    nav.php
    ---------
    <?php
    Template::render('nav.html', [
        'nav' => [
            ['url' => '/', 'text' => 'Home'],
            ['url' => '/about', 'text' => 'About'],
            ['url' => '/contact', 'text' => 'Contact']
        ]
    ]);
    ?>
    
    nav.html
    ----------
    <ul :nav:>
        <li><a href=":url:">:text:</a></li>
    </ul>

    output
    ------
    <ul>
        <li><a href="/">Home</a></li>
        <li><a href="/about">About</a></li>
        <li><a href="/contact">Contact</a></li>
    </ul>

## Objects

    person.php
    ---------
    <?php
    class Person {
        var $first;
        var $last;

        function Person($first, $last) {
            $this->first = $first;
            $this->last = $last;
        }
    }

    Template::render('person.html', [
        'person' => new Person('John', 'Doe')
    ]);
    ?>
    
    person.html
    ----------
    <table>
        <tr>
            <th>First Name</th>
            <th>Last Name</th>
        </tr>
        <tr :person:>
            <td>:first:</td>
            <td>:last:</td>
        </tr>
    </table>

    output
    ------
    <table>
        <tr>
            <th>First Name</th>
            <th>Last Name</th>
        </tr>
        <tr>
            <td>John</td>
            <td>Doe</td>
        </tr>
    </table>

## Includes

    hello.php
    ---------
    <?php
    Template::render('hello.html');
    ?>
    
    hello.html
    ----------
    <div>:hi.html:</div>

    hi.html
    ----------
    <p>Hi World</p>

    output
    ------
    <div><p>Hi World</p></div>

## Hooks

    blog.php
    ---------
    <?php
    Template::render('blog.html', [
        'entries' => [
            ['title' => 'Hello world', 'body' => '<p>Hello world</p>'],
            ['title' => 'Bye world', 'body' => '<p>Bye world</p>']
        ],
        'archived' => []
    ]);
    ?>
    
    blog.html
    ----------
    <!--the 'raw' hook allows us to print unescaped html-->
    <!--the 'else' hook allows us to print alternate content when there's no data to display-->
    <div :entries:>
        <h1>:title:</h1>
        <div :raw:>:body:</div>
    </div>
    <div :archived:>
        <!--todo-->
    </div>
    <div :else:>No archived entries</div>

    output
    ------

    <div>
        <h1>Hello world</h1>
        <p>Hello world</p>
    </div>

    <div>
        <h1>Bye world</h1>
        <p>Bye world</p>
    </div>
    <div>No archived entries</div>

##API

    Template::render(string $filename, array $data)

    Todo: document compiler