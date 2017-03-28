@echo OFF
mkdir app
svn add app
mkdir cache
svn add cache
mkdir var
svn add var
mkdir www
svn add www

svn commit -m "AS App structure committed"


echo AS http://dc-svn.garrettcounty.org/svn/dc.framework/>>svn.externals
echo www/dcfront http://dc-svn.garrettcounty.org/svn/dc.front/>>svn.externals

svn propset svn:externals -F svn.externals .

del svn.externals

svn commit -m "AS Externals added"
svn up