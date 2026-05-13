#!/bin/bash
# Fix database credentials di Database.php untuk production
python3 -c "
f=open('/var/www/geoportal/app/Config/Database.php','r')
c=f.read()
f.close()
c=c.replace(\"'username'     => 'postgres'\",\"'username'     => 'geoportal_user'\")
c=c.replace(\"'password'     => 'yayaya123'\",\"'password'     => 'Gr4vP0rt!Itb@2025'\")
c=c.replace(\"'database'     => 'MockUp'\",\"'database'     => 'geoportal'\")
c=c.replace(\"'database'     => 'gravport'\",\"'database'     => 'geoportal'\")
f=open('/var/www/geoportal/app/Config/Database.php','w')
f.write(c)
f.close()
print('Database.php fixed!')
"
systemctl restart apache2
echo "Apache restarted. Done!"
