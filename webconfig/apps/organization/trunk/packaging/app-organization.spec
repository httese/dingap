
Name: app-organization
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: Organization app summary
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Organization long description

%package core
Summary: Organization app summary - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core

%description core
Organization long description

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/organization
cp -r * %{buildroot}/usr/clearos/apps/organization/


%post
logger -p local6.notice -t installer 'app-organization - installing'

%post core
logger -p local6.notice -t installer 'app-organization-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/organization/deploy/install ] && /usr/clearos/apps/organization/deploy/install
fi

[ -x /usr/clearos/apps/organization/deploy/upgrade ] && /usr/clearos/apps/organization/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-organization - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-organization-core - uninstalling'
    [ -x /usr/clearos/apps/organization/deploy/uninstall ] && /usr/clearos/apps/organization/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/organization/controllers
/usr/clearos/apps/organization/htdocs
/usr/clearos/apps/organization/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/organization/packaging
%exclude /usr/clearos/apps/organization/tests
%dir /usr/clearos/apps/organization
/usr/clearos/apps/organization/deploy
/usr/clearos/apps/organization/language
/usr/clearos/apps/organization/libraries