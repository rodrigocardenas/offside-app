ssh -o StrictHostKeyChecking=no -i ~/.ssh/key.pem ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com << 'EOF'
echo "=== CRONTABS ==="
sudo crontab -l -u ubuntu 2>/dev/null
sudo crontab -l -u www-data 2>/dev/null
sudo crontab -l -u root 2>/dev/null
cat /etc/crontab 2>/dev/null
ls -la /etc/cron.d/ 2>/dev/null
ls -la /etc/cron.hourly/ 2>/dev/null
ls -la /etc/cron.daily/ 2>/dev/null

echo "=== SSH KEYS ==="
cat ~/.ssh/authorized_keys 2>/dev/null
sudo cat /root/.ssh/authorized_keys 2>/dev/null
sudo cat /var/www/.ssh/authorized_keys 2>/dev/null

echo "=== SUSPICIOUS PROCESSES ==="
ps aux | grep -v grep | grep -E "pulseadio|kdevtmpfsi|kinsing|xmrig|bash -i|nc -e|curl|wget"

echo "=== SUSPICIOUS DIRECTORIES (/tmp, /var/tmp, /dev/shm) ==="
sudo ls -laR /tmp/ /var/tmp/ /dev/shm/ | grep -v "^total" | grep -v "^d" | grep -v "\.$"

echo "=== RECENTLY MODIFIED PHP FILES IN WWW (Last 7 days) ==="
find /var/www/html -type f -name "*.php" -mtime -7 | grep -v "vendor/" | grep -v "storage/"

echo "=== WEBSHELL SIGNATURES ==="
sudo grep -riE "eval\s*\(|base64_decode\s*\(|system\s*\(|exec\s*\(|shell_exec\s*\(|passthru\s*\(|`.*`" /var/www/html/public/ | grep -v "vendor"
sudo grep -riE "eval\s*\(|base64_decode\s*\(|system\s*\(|exec\s*\(|shell_exec\s*\(|passthru\s*\(|`.*`" /var/www/html/app/ | grep -v "vendor"

echo "=== OPEN PORTS ==="
sudo ss -tulnp

echo "=== USERS WITH BASH ==="
cat /etc/passwd | grep -E "bash|sh"

EOF
