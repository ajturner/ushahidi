# Server specific options
set :user,  "root"
role :app, "174.129.221.96"
role :web, "174.129.221.96"
role :db, "174.129.221.96", :primary=>true
set :deploy_to, "/root/freefairelections/#{application}"
