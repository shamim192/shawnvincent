<?php

namespace App\Enums;

enum SectionEnum: string
{
    const BG = 'bg_image';

    case HOME_BANNER = 'home_banner';
    case HOME_CONTACT = 'home_contact';
    case HOME_BANNERS = 'home_banners';
    case HERO = 'hero';


    case MUSIC_BANNER = 'music_banner';
    case MUSIC_EXPLORE = 'explore';
    case MUSIC_CONTACT = 'contact';
    //Footer
    case FOOTER = 'footer';
    case SOLUTION = "solution";

}
