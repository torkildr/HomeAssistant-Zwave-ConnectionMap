# HomeAssistant-Zwave-ConnectionMap
Draws a map of the Z-wave mesh network using Graphviz.

This should be compatible with anything based on OpenZWave that has a OZW_Log.txt and a zwcfg_0xfac5e970.xml file I guess, but I haven't tried it on anything else but [Home Assistant](https://home-assistant.io/).

![Example graph](https://raw.githubusercontent.com/magma1447/HomeAssistant-Zwave-ConnectionMap/master/example/zwave-map.png)

## Installation
Start by installing required packages. The below command is based on Debian Jessie.  
`apt-get install php5-cli php-pear graphviz`

Either fetch GraphViz.php from [GitHub](https://github.com/pear/Image_GraphViz/blob/trunk/Image/GraphViz.php) (tip: press raw) or install it via pear, `pear install Image_GraphViz`.

In current stable (stretch) the first package would be *php7-cli*.

Note that it doesn't have to be installed on the same server as your Home Assistant. You can install it somewhere else and just copy the two required files that are needed to generate the connection graph.

## Usage
The controller is hard coded as Node 001. If this isn't correct, it can be changed in the source code around line 12. It might make sense to run a z-wave network heal before running this tool.  
`php -f zwave-map.php <OZW.log> <zwcfg.xml> <image.svg>`  

### Using Docker

To build the image run

`docker build .`

Then, you can run a container on the data.

The container takes the following env arguments (with defaults):

`LOG` (default: `OZW_Log.log`)
`CFG` (default: `zwcfg.log`)
`OUTPUT` (default: `graph.svg`)

Input and output must be in the volume `/data`.

Example:


`docker run -v ~/zwave:/data -e CFG=zwcfg_<hash>.xml -it <container id>`


