# Server specific options
set :user,  "root"
role :app, "votereport.in"
role :web, "votereport.in"
role :db, "votereport.in", :primary=>true
set :deploy_to, "/root/freefairelections/#{application}"
