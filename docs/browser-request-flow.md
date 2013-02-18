Browser Request Flow
========================

1. request comes into `index.php`
2. `index.php` calls `b::run('browser')`
3. `b::run()` calls `b::request()->run()`
4. `b::request()->run()` calls `b::route()->match()` to find route option
5. `b::request()->run()` instanciates the proper controller for the route
6. `b::request()->run()` calls `controller->run()` which sets `controller::content`
7. `b::request()->run()` calls `b::response()->run()`
8. `b::response()->run()` runs `b::response()->getOutputHandler()` to find proper response handler
9. `b::response()->run()` calls `handler->getContent()` to handle proper response
10. `b::response()->run()` outputs the proper content
