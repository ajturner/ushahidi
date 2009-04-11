set :stages, %w(testing production) 
set :default_stage, 'testing' 
set :stage_dir, "deploy/stages"
require 'capistrano/ext/multistage' 

set :scm, "git"
set :repository,  "git://github.com/ajturner/ushahidi.git"
set :branch, "votereportin"
set :deploy_via, :remote_cache
set :git_shallow_clone, 1
 
# Ushahidi isn't a Rails app, so assets aren't in public/javascript, et al.
set :normalize_asset_timestamps, false

set :use_sudo, false
set :runner, nil
# set :group, "wheel"
ssh_options[:compression] = false

set :application, "ushahidi"
set :keep_releases, 3

namespace :deploy do
  task :set_permissions do
    run "chown -R nobody:nobody #{current_path}/"    
  end
  
  task :start do
    run "/etc/init.d/apache2 restart"
  end
  desc "Restart Application"
  task :restart, :roles => :app do
    run "/etc/init.d/apache2 restart"
  end    
  
  task :create_directories do
    run "mkdir -p #{deploy_to} #{deploy_to}/releases #{deploy_to}/shared/system #{deploy_to}/shared/log #{deploy_to}/shared/pids "
  end
  
  # task :setup_server do
#     passenger_config = ERB.new <<-EOF    
# <VirtualHost *:80>
#     ServerName #{application}.#{hostname}
#     DocumentRoot #{current_path}/public
# </VirtualHost>
# EOF
#     put passenger_config.result, "/etc/httpd/sites/#{application}.conf" 
    # run "/etc/init.d/apache2 graceful"
  # end

  # task :setup_database do
  #   db_config = YAML.load_file("config/database.yml")
  #   user = db_config["production"]["username"]
  #   pass = db_config["production"]["password"]
  #   db = db_config["production"]["database"]
  #   status = sudo "createuser -SlqRD #{user}; echo $?", :as => "postgres"
  #   puts "WARNING: Got Bad Exit Status.  Maybe database user #{user} already exists?" if(status.to_i != 0)
  #   sudo "psql -c \"ALTER USER #{user} WITH PASSWORD '#{pass}';\" ", :as => "postgres"
  #   sudo "psql -c \"CREATE DATABASE #{db}; GRANT ALL PRIVILEGES ON DATABASE #{db} TO #{user};\" ", :as => "postgres"
  # end
  # task :drop_database do
  #   db_config = YAML.load_file("config/database.yml")
  #   user = db_config["production"]["username"]
  #   db = db_config["production"]["database"]
  #   status = sudo "dropdb #{db}", :as => "postgres"
  #   status = sudo "dropuser #{user}", :as => "postgres"
  # end  
end


# namespace :daemons do
#   desc "Start Daemons"
#   task :start, :roles => :daemons do
#     run "#{deploy_to}/current/script/daemons start"
#   end
# 
#   desc "Stop Daemons"
#   task :stop, :roles => :daemons do
#     run "#{deploy_to}/current/script/daemons stop"
#     run "sleep 5 && killall -9 ruby"
#   end
# end
# 
# desc "Link in the production database.yml" 
# task :after_update_code do
#   run "ln -nfs #{deploy_to}/#{shared_dir}/config/database.yml #{release_path}/config/database.yml" 
# end
