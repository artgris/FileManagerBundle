Chapter 4 - Security
======================================

To prevent attackers from uploading and executing PHP scripts, you should add a **.htaccess**  to enforce security restrictions.
 
Example:
     
     // .htaccess
     
     ForceType application/octet-stream
     Header set Content-Disposition attachment
     <FilesMatch "(?i)\.(gif|jpe?g|png)$">
         ForceType none
         Header unset Content-Disposition
     </FilesMatch>
     Header set X-Content-Type-Options nosniff
     
*Source:*  https://github.com/blueimp/jQuery-File-Upload/wiki/Security
