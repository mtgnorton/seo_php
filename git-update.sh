cd /home/seo_master
sudo  git reset --hard
sudo git pull
rsync -avz /home/seo_master/* /home/seo_github/ --exclude='.git'
php encode_files_linux.php

cd /home/seo_github

git add .

git commit -am "auto commit"

git push
