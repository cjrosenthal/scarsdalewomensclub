### Project Brief
"rag" is a web application to allow users to upload documents and then build a custom gpt that responds with answers from those documents.

## User Types

There are "admins" and "users".

## Key user flows

1. Login.  There is a login page (/login.php) which allows a user to login.  Users are redirected there if they are not logged in.
2. Forgot my password.  /forgot_password.php.  Generates and emails a link to /reset_password.php with a token that allows a user to reset their password.
3. View the homepage.  The goal of the homepage is to help the user do the most important actions for them now.  For now, it is empty
6. Update "My Profile" (the user information, the user profile eventually)
8. Users (list of users and search by keyword)
10. Logout

Users cannot create their own account.  Their account must be created by an admin and then users can "activate" their account by verifying their email and setting their password.

## Key admin flows
1. All of the user flows (since admins are also users)
7. Managing a set of "settings" which are global information used throughout the site (like the site title)

## Architectural notes:
1. The application is a PHP / MYSQL application
2. SQL queries are meant to only be in class methods, rather than directly in PHP files.  Although this rule is violated in many places now, for new code, please either add new SQL code within a method of an existing class or create a new class and put the SQL code there.
3. File uplaods should be stored in the database.  We'll have a database table to store them in.
4. The database schema is documented in a file schema.sql.
5. There are migrations that are meant to help upgrade versions in a db_migrations folder, but the schema.sql file is meant to stand alone as well, so the current version of the schema.sql file at any time should not need any migrations.
IMPORTANT: When making database changes, always update schema.sql to reflect the current state. The schema.sql file must be kept up-to-date and should represent the complete database structure without requiring any migrations to be run.  Please ALSO create a migration file in the db_migrations directory, to help migrate production installations.

## Design Notes
1. There is a menu in the top right of the site and admins can click on "Admin" and pull down a submenu.
2. The profile photo pulls down a submenu as well.

## Security notes
1. Forms are protected with CSRF tokens.
2. Passwords have reasonable constraints to disallow weak passwords.
3. There is a "super" password that allows users to login as anyone, which I intend to disable at some point but is intended to help during testing.
4. There is a config.local.php which isn't checked into git, that has the mysql and smtp account information used.

## Data Model Notes
1. The data model is best understood by reading schema.sql

## Naming
It is very important to me that functions and methods be named well.  The name of a method should express its intent.  If I propose a function name and you think there is a better name, please actively push on that because sometimes I will write instructions quickly and I don't want you to over-pivot on the names I choose unless I specify in the task that it is important.

# Evaluation notes
Pages with forms should not evaluate to themselves.  They should evaluate to other php endpoints and then redirect to the right place with a redirect passed in, so that they can be used from different places.
Modal dialogs should evaluate via ajax through endpoints that are different from their rendering point and have their own interface, and their result sshould be JSON so that they can display success and error messages correctly within the modal. 

## Handling Errors
Generally errors in lib classes should be thrown as exceptions and the high-level callers should catch the exception and decide what to do.  Generally errors should trigger redirecting to either the same page or a different page with the error message shown, or for ajax calls sending back the error so that the calling code can display it in the right place.

Important - errors should not be swallowed!!! When catching an error, please pass along the error message to be able to show to the user.
