sudo  git reset --hard
sudo git pull
rsync -avz /home/seo_master/* /home/seo_github/ --exclude='.git'
php encode_files_linux.php
