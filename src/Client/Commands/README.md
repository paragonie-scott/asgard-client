# Client Commands

Every class in this directory needs to extend `\ParagonIE\AsgardClient\Command`.
Only classes in this exact folder will:

 1. Be executable by running `asgard classnamehere`
 2. Be listed in the help menu

You can create subdirectories for other classes (if need be) without them being
directly exposed to the `asgard` command line interface. 

**Please use PSR-4 namespacing so these classes can be autoloaded!**