# Dependency Confusion Plugin Testing Helper

## Setup
1. Clone and run `composer update`
2. `cd /path/to/this/local/repo` and run `composer create-project composer/satis:dev-main` to install satis locally
3. Run `git clone -o origin git@github.com:nathanjosiah/dep-conf-test-package.git package-a`
4. Run `git -C package-a remote add public git@github.com:nathanjosiah/dep-conf-test-package-a-malicious.git`

## Deploying packages
1. Configure `configure.php` to have the values you want to deploy.
2. Run `php configure.php`
3. Commit and push the changes to this repo trigger a deployment to [Netlify which hosts](https://flamboyant-haibt-5db8f9.netlify.app/) the `build/` folder of this repo from satis.
4. [Go to `packagist`](https://packagist.org/packages/nathanjosiah/dep-conf-test-package-a) and trigger an update for the public repo. Manage the existing available public package version as needed.

## Auto-tests
To run all the audit scenarios for your local version of composer:

1. Add the audit plugin locally if needed/desired e.g. `composer require magento/composer-dependency-version-audit-plugin`
2. Run `php test.php`. This will read the CSV configuration and dump the results to `results/results-*.csv`
