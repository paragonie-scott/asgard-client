# ASGard - Authentic Software Guard (Client)

ASGard is a package manager that solves the [secure code delivery problem](https://defuse.ca/triangle-of-secure-code-delivery.htm).
ASGard works standalone or in conjunction with an existing package manager (e.g. [Composer](https://getcomposer.org)).

This is a pure PHP implementation of the ASGard client protocol.
All client implementations are released under all of the following public licenses:

* GNU Public License version 3
* 3-clause BSD License

We use [Toro](https://github.com/anandkunal/ToroPHP) for routing the notary
server; which is MIT licensed.

For cryptography, we use **libsodium**. [Libsodium](https://github.com/jedisct1/libsodium) 
is released under the [ISC License](https://en.wikipedia.org/wiki/ISC_license).
Libsodium is a portable fork of [NaCl](http://nacl.cr.yp.to) - a high-speed, modern
cryptography library by 
[Daniel J. Bernstein](http://cr.yp.to), 
[Tanja Lange](https://www.hyperelliptic.org/), and 
[Peter Schwabe](https://cryptojedi.org/peter/index.shtml).

Libsodium was forked (and is maintained) by [Frank Denis](https://00f.net).

## Getting Started

See the [Quick Start](docs/manual/01_quick_start) section of our manual.
