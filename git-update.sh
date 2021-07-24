cd /home/seo_master
echo "切换到seo_master目录<br>";

sudo  git reset --hard
sudo git pull
echo "<br>";
rsync -avz /home/seo_master/* /home/seo_github/ --exclude='.git'
echo "<br>";
echo "开始进行加密";
php encode_files_linux.php
echo "<br>";

cd /home/seo_github
echo "切换到seo_github目录<br>";

git add .

git commit -am "auto commit"

git push
echo "提交完成"
