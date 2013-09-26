require 'railsless-deploy'

# Application Settings
set :application, "memes.parad0x.me"
set :environment, "production"

# Roles
role :web,        "azureuser@pdx-www-1.cloudapp.net"

# Repo settings
set :repository,  "git@github.com:Parad0X/meme-generator.git"
set :scm,         "git"
set :branch,      "master"
set :git_enable_submodules, 1

# Deploy settings
set :deploy_via,        :remote_cache
set :deploy_to,         "/var/www/memes.parad0x.me"
set :keep_releases,     3
set :use_sudo,          false

# User agent forwarding
ssh_options[:forward_agent] = true
ssh_options[:auth_methods]  = ["publickey"]
ssh_options[:keys]          = ["/~Devel/Azure/azure-cert.pem"]

# Stuff
namespace :composer do
    desc "Install composer dependencies"
    task :install, :roles => :web do
            run "cd #{latest_release}; composer install"
    end
end

namespace :php_fpm do
	desc "Restart PHP FPM"
	task :restart, :roles => :web do
		run "sudo service php5-fpm restart"
	end
end

namespace :deploy do
    task :fix_permissions do
        run "cd #{latest_release}; ./bin/nginx-set-facl app/logs"
        run "cd #{latest_release}; ./bin/nginx-set-facl app/cache"
    end
end

after "deploy:update", "deploy:fix_permissions"
after "deploy:update", "composer:install"
after "deploy:update", "php_fpm:restart"
after "deploy:update", "deploy:cleanup"