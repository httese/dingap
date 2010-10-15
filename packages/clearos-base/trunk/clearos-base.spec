#------------------------------------------------------------------------------
# P A C K A G E  I N F O
#------------------------------------------------------------------------------

Name: clearos-base
Version: 6.0
Release: 0.3%{dist}
Summary: Initializes the system environment
License: Affero GPLv3 or later
Group: Applications/Modules
Source: %{name}-%{version}.tar.gz
Vendor: Point Clark Networks
Packager: Point Clark Networks
# Base product release information
Requires: app-release = %VERSION%
# Core system 
Requires: gnupg
Requires: grub >= 0.95
Requires: initscripts >= 7.93.11.EL
Requires: kernel >= 2.6.18-164
Requires: mdadm >= 1.6.0
Requires: mkinitrd >= 4.2.1.10-2
Requires: mlocate >= 0.15
Requires: module-init-tools >= 3.1
Requires: ntp
Requires: openssh-server
Requires: perl
Requires: rpm >= 4.4.2-48.3
Requires: selinux-policy-targeted >= 2.4.6
Requires: sudo >= 1.6.8
Requires: sysklogd
Requires: system-logos
Requires: vixie-cron
# Common tools used in install and upgrade scripts for app-* packages
Requires: bc
Requires: chkconfig
Requires: coreutils
Requires: findutils
Requires: gawk
Requires: grep
Requires: sed
Requires: shadow-utils
Requires: util-linux
Requires: which
Requires: /usr/bin/logger
Requires: /sbin/pidof
# TODO: remove Provides: perl(functions)
Provides: perl(functions)
Provides: indexhtml
Provides: cc-setup
Provides: app-setup
Obsoletes: cc-setup
Obsoletes: cc-shell
Obsoletes: cc-support
Obsoletes: app-setup
Obsoletes: indexhtml
Buildarch: noarch
BuildRoot: %_tmppath/%name-%version-buildroot

%description
Initializes the system environment

#------------------------------------------------------------------------------
# B U I L D
#------------------------------------------------------------------------------

%prep
%setup
%build

#------------------------------------------------------------------------------
# I N S T A L L  F I L E S
#------------------------------------------------------------------------------

%install
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT

mkdir -p -m 755 $RPM_BUILD_ROOT/usr/sbin
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/share/system/modules/setup/cleanup
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/share/system/settings
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/share/system/scripts
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/logrotate.d
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/cron.d
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/init.d
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/system/initialized

install -m 644 etc/cron.d/app-servicewatch $RPM_BUILD_ROOT/etc/cron.d/app-servicewatch
install -m 644 etc/logrotate.d/compliance $RPM_BUILD_ROOT/etc/logrotate.d/
install -m 644 etc/logrotate.d/system $RPM_BUILD_ROOT/etc/logrotate.d/
install -m 755 etc/init.d/functions-automagic $RPM_BUILD_ROOT/etc/init.d/
install -m 644 etc/system/fileextensions $RPM_BUILD_ROOT/etc/system/
install -m 755 scripts/* $RPM_BUILD_ROOT/usr/share/system/scripts/
install -m 755 sbin/addsudo $RPM_BUILD_ROOT/usr/sbin/addsudo

install -m 644 custom/organization $RPM_BUILD_ROOT/etc/system/
install -m 644 custom/locale $RPM_BUILD_ROOT/usr/share/system/settings/
install -m 644 custom/vendor $RPM_BUILD_ROOT/usr/share/system/settings/

#------------------------------------------------------------------------------
# I N S T A L L  S C R I P T
#------------------------------------------------------------------------------

%post
logger -p local6.notice -t installer "app-setup - installing"

if ( [ $1 == 1 ] && [ ! -e /etc/system/pre5x ] ); then
	touch /etc/system/initialized/setup
fi

# Add our own logs to syslog
#---------------------------

CHECKSYSLOG=`grep "^local6" /etc/syslog.conf 2>/dev/null`
if [ -z "$CHECKSYSLOG" ]; then
	logger -p local6.notice -t installer "app-setup - adding system log file to syslog"
	echo "local6.*                        /var/log/system" >> /etc/syslog.conf
	sed -i -e 's/[[:space:]]*\/var\/log\/messages/;local6.none \/var\/log\/messages/' /etc/syslog.conf
	/sbin/service syslog restart >/dev/null 2>&1
fi

# Add our own logs to syslog
#---------------------------

CHECKSYSLOG=`grep "^local5" /etc/syslog.conf 2>/dev/null`
if [ -z "$CHECKSYSLOG" ]; then
	logger -p local5.notice -t installer "app-setup - adding compliance log file to syslog"
	echo "local5.*                        /var/log/compliance" >> /etc/syslog.conf
	sed -i -e 's/[[:space:]]*\/var\/log\/messages/;local5.none \/var\/log\/messages/' /etc/syslog.conf
	/sbin/service syslog restart >/dev/null 2>&1
fi

#------------------------------------------------------------------------------
# NOTE: We de the following on upgrade *OR* install
#------------------------------------------------------------------------------

# Change to heading to avoid Red Hat trademark issues
#----------------------------------------------------

CHECKRH=`grep "^title Red Hat Linux" /boot/grub/grub.conf 2>/dev/null`
if [ ! -z "$CHECKRH" ]; then
	logger -p local6.notice -t installer "app-setup - scrubbing trademarks from old boot entries"
	sed -e 's/^title Red Hat Linux/title Linux/' /boot/grub/grub.conf > /tmp/grub.conf.new
	mv /tmp/grub.conf.new /boot/grub/grub.conf
fi

# Change kernel upgrade policy
#-----------------------------

if [ "$1" == "2" ]; then
	if [ ! -f /etc/sysconfig/kernel ]; then
		SMP=`uname -a | grep smp`
		if [ -n "$SMP" ]; then
			SMP="-smp"
		fi
		echo "UPDATEDEFAULT=yes" > /etc/sysconfig/kernel
		echo "DEFAULTKERNEL=kernel$SMP" >> /etc/sysconfig/kernel
	fi
fi

# Disable SELinux
#----------------

if [ -d /etc/selinux ]; then
	CHECK=`grep ^SELINUX= /etc/selinux/config 2>/dev/null | sed 's/.*=//'`
	if [ -z "$CHECK" ]; then
		logger -p local6.notice -t installer "app-setup - disabling SELinux with new configuration"
		echo "SELINUX=disabled" >> /etc/selinux/config
	elif [ "$CHECK" != "disabled" ]; then
		logger -p local6.notice -t installer "app-setup - disabling SELinux"
		sed -i -e 's/^SELINUX=.*/SELINUX=disabled/' /etc/selinux/config
	fi
fi

# Allow only version 2 on SSH server
#-----------------------------------

if [ -e /etc/ssh/sshd_config ]; then
	CHECKPROTOVER=`grep "^Protocol.*1" /etc/ssh/sshd_config 2>/dev/null`
	if [ -n "$CHECKPROTOVER" ]; then
		logger -p local6.notice -t installer "app-setup - upgrading to protocol 2 in SSHD configuration"
		sed -i -e 's/^Protocol.*/Protocol 2/' /etc/ssh/sshd_config
	fi

	CHECKPROTO=`grep "^Protocol" /etc/ssh/sshd_config 2>/dev/null`
	if [ -z "$CHECKPROTO" ]; then
		logger -p local6.notice -t installer "app-setup - adding protocol 2 to SSHD configuration"
		echo "Protocol 2" >> /etc/ssh/sshd_config
	fi

	CHECKPERMS=`stat --format=%a /etc/ssh/sshd_config`
	if [ "$CHECKPERMS" != "600" ]; then
		logger -p local6.notice -t installer "app-setup - changing file permission policy on sshd_config"
		chmod 0600 /etc/ssh/sshd_config
	fi
fi


#------------------------------------------------------------------------------
# U P G R A D E   S C R I P T
#------------------------------------------------------------------------------

# Changed default group on useradd
#---------------------------------

CHECK=`grep "^GROUP=100$" /etc/default/useradd 2>/dev/null`
if [ -n "$CHECK" ]; then
	logger -p local6.notice -t installer "app-setup - changing default group ID"
	sed -i -e 's/^GROUP=100$/GROUP=63000/' /etc/default/useradd
fi

# Remove old service watch crontab entry
#---------------------------------------

OLDWATCH=`grep "servicewatch" /etc/crontab 2>/dev/null`
if [ ! -z "$OLDWATCH" ]; then
	logger -p local6.notice -t installer "app-setup - removing old servicewatch from crontab"
	grep -v 'servicewatch' /etc/crontab > /etc/crontab.new
	mv /etc/crontab.new /etc/crontab
fi

# Chap/pap secrets format
#------------------------

CHECKCHAP=`grep Webconfig /etc/ppp/chap-secrets 2>/dev/null`
if [ -z "$CHECKCHAP" ]; then
	/usr/share/system/scripts/chap-convert
fi

# Turn off daemons that always want to start at boot on upgrades
#---------------------------------------------------------------

# TODO
if [ -e /etc/init.d/gpm ]; then
	chkconfig --level 2345 gpm off
fi  
if [ -e /etc/rc.d/init.d/mdmpd ]; then
	chkconfig --level 2345 mdmpd off
fi
if [ -e /etc/rc.d/init.d/xfs ]; then
	chkconfig --level 2345 xfs off
fi
if [ -e /etc/rc.d/init.d/mcstrans ]; then
	chkconfig --level 2345 mcstrans off
fi
if [ -e /etc/rc.d/init.d/auditd ]; then
	chkconfig --level 2345 auditd off
fi
if [ -e /etc/rc.d/init.d/avahi-daemon ]; then
	chkconfig --level 2345 avahi-daemon off
fi

# Add swap fix
#-------------
#
# TODO: is this still an issue?
# echo "/usr/share/system/scripts/swapfix" >> /etc/rc.d/rc.local

# Update grub titles
#-------------------

/usr/share/system/scripts/updategrub

# Add syslog for suva
#--------------------

CHECKSYSLOG=`grep "^local0" /etc/syslog.conf 2>/dev/null`
if [ -z "$CHECKSYSLOG" ]; then
	logger -p local6.notice -t installer "app-setup - adding suva log file to syslog"
	echo "local0.*                        /var/log/suva" >> /etc/syslog.conf
	sed -i -e 's/[[:space:]]*\/var\/log\/messages/;local0.none \/var\/log\/messages/' /etc/syslog.conf
	/sbin/service syslog restart >/dev/null 2>&1
fi

# Sudo policies
#--------------

CHECKSUDO=`grep '^Defaults:webconfig !syslog' /etc/sudoers 2>/dev/null`
if [ -z "$CHECKSUDO" ]; then
    logger -p local6.notice -t installer "app-setup - adding syslog policy for webconfig"
    echo 'Defaults:webconfig !syslog' >> /etc/sudoers
    chmod 0400 /etc/sudoers
fi

CHECKSUDO=`grep '^Defaults:root !syslog' /etc/sudoers 2>/dev/null`
if [ -z "$CHECKSUDO" ]; then
    logger -p local6.notice -t installer "app-setup - adding syslog policy for root"
    echo 'Defaults:root !syslog' >> /etc/sudoers
    chmod 0400 /etc/sudoers
fi

CHECKSUDO=`grep "^admin ALL" /etc/sudoers 2>/dev/null`
if [ ! -z "$CHECKSUDO" ]; then
    logger -p local6.notice -t installer "app-setup - changing webconfig user"
    sed -i -e 's/^admin ALL/webconfig ALL/g' /etc/sudoers
fi

CHECKTTY=`grep '^Defaults.*requiretty' /etc/sudoers 2>/dev/null`
if [ -n "$CHECKTTY" ]; then
    logger -p local6.notice -t installer "app-setup - removing requiretty from sudoers"
	sed -i -e 's/^Defaults.*requiretty/# Defaults    requiretty/' /etc/sudoers
    chmod 0400 /etc/sudoers
fi

# slocate/mlocate upgrade
#------------------------

CHECK=`grep '^export' /etc/updatedb.conf 2>/dev/null`
if [ -n "$CHECK" ]; then
	CHECK=`grep '^export' /etc/updatedb.conf.rpmnew 2>/dev/null`
	if ( [ -e "/etc/updatedb.conf.rpmnew" ] && [ -z "$CHECK" ] ); then
    	logger -p local6.notice -t installer "app-setup - migrating configuration from slocate to mlocate"
		cp -p /etc/updatedb.conf.rpmnew /etc/updatedb.conf
	else
    	logger -p local6.notice -t installer "app-setup - creating default configuration for mlocate"
		echo "PRUNEFS = \"auto afs iso9660 sfs udf\"" > /etc/updatedb.conf
		echo "PRUNEPATHS = \"/afs /media /net /sfs /tmp /udev /var/spool/cups /var/spool/squid /var/tmp\"" >> /etc/updatedb.conf
	fi
fi


# Miscellaneous clean up
#-----------------------

# TODO: move to syswatch
rm -f /etc/init.d/*rpmsave /etc/init.d/*rpmnew 2>/dev/null
rm -f /etc/cron.d/*rpmsave /etc/cron.d/*rpmnew 2>/dev/null

#------------------------------------------------------------------------------
# U N I N S T A L L  S C R I P T
#------------------------------------------------------------------------------

%preun
if [ "$1" = 0 ]; then
	logger -p local6.notice -t installer "app-setup - uninstalling"
fi


#------------------------------------------------------------------------------
# C L E A N  U P
#------------------------------------------------------------------------------

%clean
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT


#------------------------------------------------------------------------------
# F I L E S
#------------------------------------------------------------------------------

%files
%defattr(-,root,root)
%dir /etc/system
/etc/cron.d/app-servicewatch
/etc/logrotate.d/compliance
/etc/logrotate.d/system
/etc/init.d/functions-automagic
%dir /etc/system/initialized
%config(noreplace) /etc/system/organization
/etc/system/fileextensions
/usr/sbin/addsudo
/usr/share/system/scripts
/usr/share/system/modules/setup/
/usr/share/system/settings/locale
/usr/share/system/settings/vendor
