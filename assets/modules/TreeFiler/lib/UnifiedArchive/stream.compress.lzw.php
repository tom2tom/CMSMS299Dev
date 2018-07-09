<?php
namespace TreeFiler\UnifiedArchive;

stream_wrapper_register('compress.lzw', __NAMESPACE__.'\\LzwStreamWrapper');
