# 원격 서버(B장비) 설치 가이드

- **서버**: 192.168.2.227 (공인 IP: 210.120.112.61)
- **OS**: Rocky Linux 9.5
- **SSH 포트**: 50310
- **계정**: nautes
- **작업일**: 2026-02-06

---

## 1. SSH 접속 준비

### 1-1. 방화벽 포트 오픈 (원격 서버에서 실행)
```bash
# firewalld 사용 시
firewall-cmd --permanent --add-port=50310/tcp
firewall-cmd --reload

# 또는 iptables 사용 시
iptables -I INPUT -p tcp --dport 50310 -j ACCEPT
iptables-save > /etc/sysconfig/iptables
```

### 1-2. SSH 키 등록 (원격 서버에서 실행)
```bash
mkdir -p ~/.ssh
chmod 700 ~/.ssh
echo 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQAB... asterisk@IPPBX02-Act' >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

### 1-3. sudoers 설정 (원격 서버에서 실행)
```bash
sudo visudo
# 아래 줄 추가:
nautes ALL=(ALL) NOPASSWD: ALL
```

### 1-4. 접속 테스트 (A장비에서 실행)
```bash
ssh -p 50310 -o StrictHostKeyChecking=no nautes@210.120.112.61 "uname -a"
```

---

## 2. Asterisk 20.17.0 설치

### 2-1. 소스 파일 복사 (A장비에서 실행)
```bash
scp -P 50310 /usr/src/asterisk-20-current.tar.gz nautes@210.120.112.61:/tmp/
```

### 2-2. 소스 추출 (원격 서버)
```bash
sudo bash -c 'cd /usr/src && tar xzf /tmp/asterisk-20-current.tar.gz'
```

### 2-3. 컴파일 및 설치 (원격 서버)
```bash
cd /usr/src/asterisk-20.17.0

# configure
sudo ./configure --with-jansson-bundled --with-pjproject-bundled

# 컴파일 (CPU 코어 수만큼 병렬)
sudo make -j$(nproc)

# 설치
sudo make install
```

### 2-4. systemd 서비스 파일 생성 (원격 서버)
```bash
sudo cat > /etc/systemd/system/asterisk.service << 'EOF'
[Unit]
Description=Asterisk PBX and telephony daemon
After=network.target mariadb.service
Wants=network.target

[Service]
Type=simple
User=asterisk
Group=asterisk
ExecStartPre=/bin/rm -f /run/asterisk/asterisk.ctl
ExecStart=/usr/sbin/asterisk -f -U asterisk -G asterisk
ExecStop=/usr/sbin/asterisk -rx "core stop now"
ExecReload=/usr/sbin/asterisk -rx "core reload"
Restart=on-failure
RestartSec=5
LimitNOFILE=65536
RuntimeDirectory=asterisk
RuntimeDirectoryMode=0755
AmbientCapabilities=CAP_NET_BIND_SERVICE
CapabilityBoundingSet=CAP_NET_BIND_SERVICE
KillMode=process

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
```

### 2-5. 디렉토리 권한 설정 및 서비스 시작 (원격 서버)
```bash
sudo chown -R asterisk:asterisk /var/run/asterisk
sudo chown -R asterisk:asterisk /var/lib/asterisk
sudo chown -R asterisk:asterisk /usr/lib/asterisk

sudo systemctl enable asterisk
sudo systemctl start asterisk

# 확인
sudo asterisk -rx 'core show version'
# 출력: Asterisk 20.17.0
```

---

## 3. FreePBX 17 모듈 설치

### 3-1. 전체 모듈 설치 (원격 서버)
```bash
sudo fwconsole ma installall
sudo fwconsole ma install arsauth
sudo fwconsole reload
```

### 3-2. 모듈 업그레이드 (원격 서버)
```bash
sudo fwconsole ma upgradeall
sudo fwconsole reload
```

---

## 4. arsauth 모듈 및 WEB 디렉토리 복사

### 4-1. A장비에서 압축 (A장비에서 실행)
```bash
# arsauth 모듈
cd /var/www/html/admin/modules
tar czf /tmp/arsauth.tar.gz arsauth/

# WEB 디렉토리
cd /home/asterisk
tar czf /tmp/WEB.tar.gz WEB/
```

### 4-2. 원격 서버로 전송 (A장비에서 실행)
```bash
scp -P 50310 /tmp/arsauth.tar.gz nautes@210.120.112.61:/tmp/
scp -P 50310 /tmp/WEB.tar.gz nautes@210.120.112.61:/tmp/
```

### 4-3. 압축 해제 및 권한 설정 (원격 서버)
```bash
# arsauth 모듈
sudo bash -c 'cd /var/www/html/admin/modules && tar xzf /tmp/arsauth.tar.gz'
sudo chown -R asterisk:asterisk /var/www/html/admin/modules/arsauth/

# WEB 디렉토리
sudo mkdir -p /home/asterisk
sudo bash -c 'cd /home/asterisk && tar xzf /tmp/WEB.tar.gz'
sudo chown -R asterisk:asterisk /home/asterisk/WEB

# 임시 파일 정리
sudo rm -f /tmp/arsauth.tar.gz /tmp/WEB.tar.gz /tmp/asterisk-20-current.tar.gz
```

---

## 5. Apache httpd 설정

### 5-1. httpd 설정 파일 복사 (A장비에서 실행)
```bash
tar czf /tmp/httpd_conf.tar.gz -C /etc httpd/conf/httpd.conf httpd/conf.d/ httpd/conf.modules.d/
scp -P 50310 /tmp/httpd_conf.tar.gz nautes@210.120.112.61:/tmp/
```

### 5-2. 설정 적용 (원격 서버)
```bash
# 기존 설정 백업
sudo cp -a /etc/httpd/conf/httpd.conf /etc/httpd/conf/httpd.conf.bak
sudo cp -a /etc/httpd/conf.d /etc/httpd/conf.d.bak

# 새 설정 적용
sudo bash -c 'cd /etc && tar xzf /tmp/httpd_conf.tar.gz'

# ServerName 변경 (원격 서버 IP로)
sudo sed -i "s/ServerName 121.254.239.53/ServerName 210.120.112.61/" /etc/httpd/conf/httpd.conf
```

### 5-3. VirtualHost 설정 (원격 서버)
```bash
sudo mkdir -p /etc/httpd/sites-enabled

sudo cat > /etc/httpd/sites-enabled/vhost.conf << 'EOF'
<VirtualHost *:80>
    ServerName 210.120.112.61
    ServerAdmin 9620lim@gmail.com

    Alias /IPPBX /var/www/html
    <Directory "/var/www/html">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    Alias /API /home/asterisk/WEB/J_IVR
    <Directory "/home/asterisk/WEB/J_IVR">
        Options FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>

    ErrorLog  /var/log/httpd/error_log
    CustomLog /var/log/httpd/access_log common
</VirtualHost>
EOF
```

### 5-4. 문법 체크 및 재시작 (원격 서버)
```bash
sudo httpd -t
sudo systemctl restart httpd
```

---

## 6. PHP-FPM 소켓 권한 수정

### 6-1. www.conf 수정 (원격 서버)
```bash
sudo cp /etc/php-fpm.d/www.conf /etc/php-fpm.d/www.conf.bak

# listen.acl_users 주석처리
sudo sed -i "s/^listen.acl_users/;listen.acl_users/" /etc/php-fpm.d/www.conf

# 소켓 소유자 설정 추가
echo "listen.owner = asterisk" | sudo tee -a /etc/php-fpm.d/www.conf
echo "listen.group = asterisk" | sudo tee -a /etc/php-fpm.d/www.conf
echo "listen.mode = 0660" | sudo tee -a /etc/php-fpm.d/www.conf
```

### 6-2. 서비스 재시작 (원격 서버)
```bash
sudo systemctl restart php-fpm
sudo systemctl restart httpd

# 소켓 확인 (asterisk:asterisk 소유여야 함)
ls -la /run/php-fpm/www.sock
```

---

## 7. FreePBX 관리자 계정 설정

```bash
# 기존 계정 삭제 후 새 계정 생성
sudo mysql -u root asterisk -e "
  DELETE FROM ampusers WHERE username IN ('pbxadmin','root');
  INSERT INTO ampusers (username, password_sha1, sections)
  VALUES ('nautes', '22151b37643b7e5904a33467666623b49498699a', '*');
"
```

- **ID**: nautes
- **PW**: @skdnxptm1@#@

---

## 8. 최종 설치 결과

| 항목 | 버전/상태 |
|------|-----------|
| **OS** | Rocky Linux 9.5 |
| **Asterisk** | 20.17.0 (active, running) |
| **FreePBX** | 17.0.25 (66개 모듈 활성화) |
| **Apache httpd** | active, running |
| **MariaDB** | 10.5 (active, running) |
| **PHP** | 8.3.20 (PHP-FPM) |
| **arsauth 모듈** | 0.0.1 설치됨 |
| **WEB 디렉토리** | /home/asterisk/WEB/ |

### 서비스 확인 명령어
```bash
sudo systemctl status asterisk httpd mariadb php-fpm
sudo asterisk -rx 'core show version'
sudo fwconsole -V
sudo fwconsole ma list
```
