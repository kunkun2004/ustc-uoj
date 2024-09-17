#include "testlib.h"
#include <string>
#include <vector>
#include <sstream>

using namespace std;

int main(int argc, char * argv[])
{
	registerTestlibCmd(argc, argv);

	int n = 0;
	//while (1)
	{
		std::string j = ans.readString();
		std::string i = ouf.readString();

//		if (j == "" && i=="")
//			break;

		n++;

		if (j != i)
			quitf(_wa, "%d%s lines differ", n, englishEnding(n).c_str());
	}
	if (n == 1)
		quitf(_ok, "single line");
	quitf(_ok, "%d lines", n);
}
