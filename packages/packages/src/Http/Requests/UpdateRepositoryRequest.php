<?php

namespace Froxlor\Packages\Http\Requests;

class UpdateRepositoryRequest extends StoreRepositoryRequest
{
    // Same validation rules as StoreRepositoryRequest — updating a repository still requires a
    // full replacement payload (name/type/url), not a partial patch.
}
