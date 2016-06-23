timhysniu/wp-cli-template
=========================


Quick links: [Installing](#installing) | [Usage](#usage) | [Contributing](#contributing)

## Installing

Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with `wp package install timhysniu/wp-cli-template`

## Usage

For list_all and find you may use a --format to specify the format.
Default format is table but you may change that to json or csv.

```
wp template list_all --format=json
```

## list_all

This sub-command will list all templates that are currently in use.

```
wp template list_all
wp template list_all --grep=home.php --format=json
```

## find

This sub-command will find all pages that are using a template. You may specify more than
one template and can also filter post ID's (comma separated for more than one)

```
wp template find default home.php page-template1.php
```

The output that you will see should look something like this if format is not csv or json:

```
+---------+----------+
| post_id | template |
+---------+----------+
| 2       | default  |
| 454     | default  |
| 771     | default  |
| 1029    | default  |
+---------+----------+
```

### change

This sub-command will change template for all posts if at least one of the filters is used.
You use any of the filters: 
post_id   - comma separated list of post ID's
template  - current template

This exampe will change the template to "page-template1.php" for all posts that have 
"default" template selected:

```
wp template change page-template-1.php --template=default
Success: Sucessfully executed. 4 posts updated
```

```
wp template change default --post_id=454,2
Success: Sucessfully executed. 2 posts updated
```
