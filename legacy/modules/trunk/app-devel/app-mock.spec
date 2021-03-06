#------------------------------------------------------------------------------
# P A C K A G E  I N F O
#------------------------------------------------------------------------------

Name: app-mock
Version: 5.2
Release: 3.1
Summary: Developer tools
License: GPL
Group: Applications/Modules
Source: %{name}-%{version}.tar.gz
Vendor: ClearFoundation
Packager: ClearFoundation
Requires: mock
Requires: app-setup = 5.2
BuildRoot: %_tmppath/%name-%version-buildroot

%description
Developer tools

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

mkdir -p -m 755 $RPM_BUILD_ROOT/etc/mock

install -m 644 clearos-5-i386-base.cfg $RPM_BUILD_ROOT/etc/mock/
install -m 644 clearos-5-i386-iso.cfg $RPM_BUILD_ROOT/etc/mock/
install -m 644 clearos-5-x86_64-base.cfg $RPM_BUILD_ROOT/etc/mock/
install -m 644 clearos-5-x86_64-iso.cfg $RPM_BUILD_ROOT/etc/mock/

#------------------------------------------------------------------------------
# I N S T A L L  S C R I P T
#------------------------------------------------------------------------------

%post
logger -p local6.notice -t installer "app-mock - installing"

#------------------------------------------------------------------------------
# U N I N S T A L L  S C R I P T
#------------------------------------------------------------------------------

%preun
if [ "$1" = 0 ]; then
	logger -p local6.notice -t installer "app-mock - uninstalling"
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
%defattr (-,root,root)
/etc/mock/clearos-5-i386-base.cfg    
/etc/mock/clearos-5-i386-iso.cfg     
/etc/mock/clearos-5-x86_64-base.cfg  
/etc/mock/clearos-5-x86_64-iso.cfg   
