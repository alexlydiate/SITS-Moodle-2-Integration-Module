A quick note in explanation:

YUI is in the Moodle core, but as of 1.9.12 it is version 2.6, which is too old, frankly.
Therefore, the specific 2.8.2 components needed are in the ./yui directory.

Prototype is legacy; a library used only for the add cohort form pop-up, which people
wanted to keep from the old block to give users a familiar feel.  Once we bin this 
GUI for a revamped version we will loose this dependancy.

And, given the dusty nature of the YUI library in the core here anyway, we are
beginning to feel an urge lean away from that towards the goodness of jQuery for 
the next version.
