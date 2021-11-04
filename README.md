# Dependency Confusion Plugin Testing Helper

## Auto-tests

1. Copy `config/test-config.csv.dist` to `config/test-config.csv` and configure with the desired scenarios. 
   
   You can run `php bin/generate-all-cases.php` to generate all the possible use cases automatically and edit as you need to.
2. Run `php bin/test.php`. This will read the CSV configuration and dump the results to `results/results-*.csv`

## Deploying packages
### Setup

1. Clone and run `composer update`
2. `cd /path/to/this/local/repo` and run `composer create-project composer/satis:dev-main` to install satis locally
3. Run `git clone -o origin git@github.com:nathanjosiah/dep-conf-test-package.git package-a`
4. Run `git -C package-a remote add public git@github.com:nathanjosiah/dep-conf-test-package-a-malicious.git`
5. Run `cp config/satis.json.dist config/satis.json`
5. Run `cp config/deploy-config.php.dist config/deploy-config.php`

### Deploy
5. Configure `config/deploy-config.php` to have the values you want to deploy.
6. Run `php bin/deploy-packages.php`
7. Commit and push the changes to this repo trigger a deployment to [Netlify which hosts](https://flamboyant-haibt-5db8f9.netlify.app/) the `build/` folder of this repo from satis.
8. [Go to `packagist`](https://packagist.org/packages/nathanjosiah/dep-conf-test-package-a) and trigger an update for the public repo. Manage the existing available public package version as needed.
